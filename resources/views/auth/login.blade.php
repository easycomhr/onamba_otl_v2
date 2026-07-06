<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ __('ui.auth.login_title') }} - OTMS</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon_128x128.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        blue: { 500:'#3b82f6', 600:'#2563eb', 700:'#1d4ed8' },
                        gray: { 50:'#f9fafb', 300:'#d1d5db', 500:'#6b7280', 800:'#1f2937' },
                        red:  { 500:'#ef4444' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4 font-sans text-[16px]">

<div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-6 sm:p-8">

    <div class="text-center mb-8">
        <img src="{{ asset('images/onamba_logo.png') }}" alt="Onamba" style="width:auto;display:inline-block" class="mb-4">
        @if(!empty($adminPortal))
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-700 font-semibold text-sm rounded-full mb-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                </svg>
                Admin Portal
            </span>
        @endif
        <p class="text-gray-500 mt-2 text-base">{{ __('ui.auth.login_subtitle') }}</p>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('login') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="portal" value="{{ !empty($adminPortal) ? 'admin' : 'employee' }}">

        <div>
            <label for="employee_id" class="block text-base font-semibold text-gray-800 mb-2">{{ __('ui.auth.employee_id') }}</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <input type="text" id="employee_id" name="employee_id" value="{{ old('employee_id') }}"
                       placeholder="{{ __('ui.auth.employee_id_placeholder') }}" required
                       class="block w-full pl-11 pr-4 py-3 text-base text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
            </div>
        </div>

        <div>
            <label for="password" class="block text-base font-semibold text-gray-800 mb-2">{{ __('ui.auth.password') }}</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <input type="password" id="password" name="password" placeholder="{{ __('ui.auth.password_placeholder') }}" required
                       class="block w-full pl-11 pr-12 py-3 text-base text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-blue-600 focus:outline-none transition-colors">
                    <svg id="eye-icon" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg id="eye-slash-icon" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit"
                class="w-full min-h-[48px] bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-lg uppercase rounded-lg shadow-md transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-blue-300">
            {{ __('ui.auth.login_button') }}
        </button>
    </form>

    <div class="mt-6 text-center">
        <span class="text-base text-gray-500">{{ __('ui.auth.forgot_password') }}</span>
    </div>

    <div class="mt-6 flex items-center justify-center gap-2">
        @foreach(['vi' => '🇻🇳 Tiếng Việt', 'en' => '🇬🇧 English', 'ja' => '🇯🇵 日本語'] as $code => $label)
            @if(app()->getLocale() === $code)
                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-sm font-semibold">{{ $label }}</span>
            @else
                <a href="{{ route('locale.switch', $code) }}"
                   class="px-3 py-1 rounded-full text-sm text-gray-500 hover:bg-gray-100 transition-colors">{{ $label }}</a>
            @endif
        @endforeach
    </div>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const eye = document.getElementById('eye-icon');
        const eyeSlash = document.getElementById('eye-slash-icon');
        if (input.type === 'password') {
            input.type = 'text';
            eye.classList.add('hidden');
            eyeSlash.classList.remove('hidden');
        } else {
            input.type = 'password';
            eye.classList.remove('hidden');
            eyeSlash.classList.add('hidden');
        }
    }
</script>
</body>
</html>
