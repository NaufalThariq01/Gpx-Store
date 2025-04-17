<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\CartItem;
use App\Models\Promo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    /**
     * Display the cart page
     */
    public function index()
    {
        $cartItems = $this->getCartItems();
        
        // Calculate cart totals
        $subtotal = 0;
        $discount = 0;
        
        foreach ($cartItems as $item) {
            $price = $item->product->discount_price ?? $item->product->price;
            $subtotal += $price * $item->quantity;
            
            // Calculate discount if applicable
            if ($item->product->discount_price && $item->product->discount_price < $item->product->price) {
                $discount += ($item->product->price - $item->product->discount_price) * $item->quantity;
            }
        }
        
        $shipping = $cartItems->count() > 0 ? 10000 : 0; // Default shipping cost
        $tax = round($subtotal * 0.11); // 11% tax rate
        $total = $subtotal + $shipping + $tax - $discount;
        
        return view('cart', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
        ]);
    }
    
    /**
     * Add an item to the cart.
     */
    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);
        
        $productId = $request->product_id;
        $quantity = $request->quantity;
        
        // Check if product exists and has stock
        $product = Product::findOrFail($productId);
        
        if (isset($product->stock) && $product->stock < $quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough stock available',
                'available_stock' => $product->stock
            ], 422);
        }
        
        $sessionId = Session::getId();
        $userId = Auth::id();
        
        // Find existing cart item
        $cartItem = CartItem::where(function ($query) use ($sessionId, $userId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->where('product_id', $productId)->first();
        
        if ($cartItem) {
            // Update quantity if item already exists
            $cartItem->quantity += $quantity;
            $cartItem->save();
        } else {
            // Create new cart item
            CartItem::create([
                'user_id' => $userId,
                'session_id' => $userId ? null : $sessionId,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }
        
        // Get updated cart count for response
        $cartCount = $this->getCartItems()->sum('quantity');
        
        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'cart_count' => $cartCount
        ]);
    }
    
    /**
     * Update cart item quantity
     */
    public function updateCart(Request $request)
    {
        $request->validate([
            'cart_item_id' => 'required|exists:cart_items,id',
            'quantity' => 'required|integer|min:0',
        ]);
        
        $cartItemId = $request->cart_item_id;
        $quantity = $request->quantity;
        
        $cartItem = CartItem::findOrFail($cartItemId);
        
        // Verify the cart item belongs to the current user/session
        if (!$this->verifyCartItemOwnership($cartItem)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        if ($quantity == 0) {
            // Remove the item if quantity is zero
            $cartItem->delete();
            $message = 'Item removed from cart';
        } else {
            // Update quantity
            $cartItem->quantity = $quantity;
            $cartItem->save();
            $message = 'Cart updated successfully';
        }
        
        // Recalculate cart totals
        $subtotal = $this->getCartItems()->sum(function ($item) {
            $price = $item->product->discount_price ?? $item->product->price;
            return $price * $item->quantity;
        });
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'cart_count' => $this->getCartItems()->sum('quantity'),
            'subtotal' => number_format($subtotal, 0, ',', '.'),
        ]);
    }
    
    /**
     * Remove an item from the cart
     */
    public function removeFromCart(Request $request)
    {
        $request->validate([
            'cart_item_id' => 'required|exists:cart_items,id',
        ]);
        
        $cartItemId = $request->cart_item_id;
        
        $cartItem = CartItem::findOrFail($cartItemId);
        
        // Verify the cart item belongs to the current user/session
        if (!$this->verifyCartItemOwnership($cartItem)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $cartItem->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
            'cart_count' => $this->getCartItems()->sum('quantity')
        ]);
    }
    
    /**
     * Clear all items from the cart
     */
    public function clearCart()
    {
        $sessionId = Session::getId();
        $userId = Auth::id();
        
        if ($userId) {
            CartItem::where('user_id', $userId)->delete();
        } else {
            CartItem::where('session_id', $sessionId)->delete();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully'
        ]);
    }
    
    /**
     * Get all cart items for the current user/session
     */
    private function getCartItems()
    {
        $sessionId = Session::getId();
        $userId = Auth::id();
        
        return CartItem::with('product')
            ->where(function ($query) use ($sessionId, $userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->latest()
            ->get();
    }
    
    /**
     * Verify a cart item belongs to the current user/session
     */
    private function verifyCartItemOwnership($cartItem)
    {
        $sessionId = Session::getId();
        $userId = Auth::id();
        
        if ($userId && $cartItem->user_id == $userId) {
            return true;
        }
        
        if (!$userId && $cartItem->session_id == $sessionId) {
            return true;
        }
        
        return false;
    }

 
}