<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Accept Partnership Invitation - {{ config('app.name') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-amber-100">
                <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.196-2.196M17 20H7m10 0v-2c0-5.523-4.477-10-10-10s-10 4.477-10 10v2m10 0H7m0 0v-2a3 3 0 013-3h4a3 3 0 013 3v2m-10 0h10" />
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Partnership Invitation
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                You've been invited to become a partner
            </p>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <div class="mb-6 p-4 bg-amber-50 rounded-lg border border-amber-200">
                <h3 class="text-lg font-medium text-amber-800 mb-2">Partnership Details</h3>
                <div class="space-y-2 text-sm text-amber-700">
                    <div><strong>Store:</strong> {{ $storeName }}</div>
                    <div><strong>Ownership:</strong> {{ $ownershipPercentage }}%</div>
                    <div><strong>Email:</strong> {{ $partnerEmail }}</div>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 rounded-lg border border-red-200">
                    <ul class="text-sm text-red-600">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-50 rounded-lg border border-red-200">
                    <p class="text-sm text-red-600">{{ session('error') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('partnership.accept.process', $token) }}" class="space-y-6">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input 
                        id="name" 
                        name="name" 
                        type="text" 
                        autocomplete="name" 
                        required 
                        value="{{ old('name') }}"
                        class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-amber-500 focus:border-amber-500 focus:z-10 sm:text-sm" 
                        placeholder="Enter your full name"
                    >
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input 
                        id="email" 
                        name="email" 
                        type="email" 
                        value="{{ $partnerEmail }}"
                        readonly
                        class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 bg-gray-50 text-gray-500 rounded-md sm:text-sm" 
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input 
                        id="password" 
                        name="password" 
                        type="password" 
                        autocomplete="new-password" 
                        required 
                        class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-amber-500 focus:border-amber-500 focus:z-10 sm:text-sm" 
                        placeholder="Choose a strong password"
                    >
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        type="password" 
                        autocomplete="new-password" 
                        required 
                        class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-amber-500 focus:border-amber-500 focus:z-10 sm:text-sm" 
                        placeholder="Confirm your password"
                    >
                </div>

                <div>
                    <button 
                        type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500"
                    >
                        Accept Partnership & Create Account
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t border-gray-200">
                <p class="text-xs text-gray-500 text-center">
                    By accepting this invitation, you agree to become a partner in {{ $storeName }} with {{ $ownershipPercentage }}% ownership.
                    This invitation expires in 7 days from when it was sent.
                </p>
            </div>
        </div>
    </div>
</body>
</html>