@extends('layouts.app')

@section('content')
<div class="py-16 bg-gray-50">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden p-10">
            <div class="text-center">
                <!-- Email verification icon -->
                <div class="mx-auto h-20 w-20 rounded-full bg-blue-100 flex items-center justify-center">
                    <svg class="h-10 w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Verifikasi Alamat Email Anda</h2>
                <p class="mt-2 text-gray-600">
                    Terima kasih telah mendaftar! Sebelum memulai, bisakah Anda memverifikasi alamat email Anda dengan mengklik tautan yang kami kirimkan melalui email kepada Anda? Jika Anda tidak menerima email tersebut, kami akan dengan senang hati mengirimkan email lainnya.
                </p>
                
                @if (session('status') == 'verification-link-sent')
                    <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    Tautan verifikasi baru telah dikirim ke alamat email yang Anda berikan saat pendaftaran.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
                
                <div class="mt-8">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 shadow-md hover:shadow-lg">
                            Kirim Ulang Email Verifikasi
                        </button>
                    </form>
                    
                    <div class="mt-4">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-4 rounded-lg transition duration-300">
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection