<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'TutorPay') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <div class="min-h-screen flex flex-col items-center justify-center px-6">
            <div class="w-full max-w-xl text-center">
                <h1 class="text-4xl font-bold tracking-tight">TutorPay</h1>
                <p class="mt-3 text-gray-600">
                    Track monthly class fees, record payments and keep an eye on outstanding balances.
                </p>

                @if (Route::has('login'))
                    <div class="mt-8 flex items-center justify-center gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}"
                               class="rounded-md bg-gray-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-700">
                                Go to dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="rounded-md bg-gray-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-700">
                                Log in
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="rounded-md border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                    Register
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    </body>
</html>
