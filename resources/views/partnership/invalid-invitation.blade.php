<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>Invalid Invitation - {{ config('app.name') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Invalid Invitation
            </h2>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <div class="text-center">
                <p class="text-gray-600 mb-6">
                    {{ $message ?? 'This invitation link is invalid or has expired.' }}
                </p>
                
                <div class="space-y-4">
                    <p class="text-sm text-gray-500">
                        Possible reasons:
                    </p>
                    <ul class="text-sm text-gray-500 text-left space-y-1">
                        <li>• The invitation has expired (7 days limit)</li>
                        <li>• The invitation has already been used</li>
                        <li>• The invitation link is incorrect</li>
                        <li>• The partnership has been cancelled</li>
                    </ul>
                </div>

                <div class="mt-8">
                    <p class="text-sm text-gray-600 mb-4">
                        If you believe this is an error, please contact the company owner who sent you the invitation.
                    </p>
                    
                    <a 
                        href="{{ route('filament.admin.auth.login') }}" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500"
                    >
                        Go to Login Page
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>