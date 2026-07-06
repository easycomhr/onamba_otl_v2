<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'OTMS')</title>
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
                        blue:  { 50:'#eff6ff',600:'#2563eb',700:'#1d4ed8',800:'#1e40af' },
                        green: { 50:'#f0fdf4',100:'#dcfce3',600:'#16a34a',700:'#15803d' },
                        gray:  { 50:'#f9fafb',100:'#f3f4f6',200:'#e5e7eb',300:'#d1d5db',500:'#6b7280',600:'#4b5563',800:'#1f2937' },
                        red:   { 50:'#fef2f2',500:'#ef4444' },
                        yellow:{ 50:'#fefce8',100:'#fef9c3',600:'#ca8a04',700:'#a16207',800:'#854d0e' },
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-200 flex justify-center min-h-screen font-sans text-[16px]">

<div class="w-full max-w-md bg-gray-50 min-h-screen md:min-h-[850px] md:rounded-[2.5rem] md:shadow-2xl flex flex-col relative overflow-hidden md:my-8">

    <div class="flex justify-between items-center px-4 py-2 bg-white border-b border-gray-100 text-sm">
        <div>
            @if($isApprover)
                <a href="{{ route('employee.team-approvals.index') }}"
                   class="inline-flex items-center gap-2 rounded-full bg-yellow-100 text-yellow-800 border border-yellow-300 px-3 py-1 font-semibold hover:bg-yellow-200 transition-colors">
                    <span>{{ __('ui.nav.team_approvals') }}</span>
                    <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full bg-yellow-600 text-white text-xs">!</span>
                </a>
            @endif
            @if(auth()->user()?->admin_role_id)
                <a href="{{ route('admin.dashboard') }}"
                   class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1 font-semibold hover:bg-blue-100 transition-colors text-xs ml-2">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                    Admin
                </a>
            @endif
        </div>

        @php($currentLocale = app()->getLocale())
        <div class="rounded-lg overflow-hidden flex border border-gray-200 text-xs font-bold">
            <a href="{{ route('locale.switch', ['locale' => 'vi']) }}"
               class="px-3 py-1.5 transition-colors {{ $currentLocale === 'vi' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                VI
            </a>
            <a href="{{ route('locale.switch', ['locale' => 'en']) }}"
               class="px-3 py-1.5 transition-colors border-x border-gray-200 {{ $currentLocale === 'en' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                EN
            </a>
            <a href="{{ route('locale.switch', ['locale' => 'ja']) }}"
               class="px-3 py-1.5 transition-colors {{ $currentLocale === 'ja' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                JP
            </a>
        </div>
    </div>

    @yield('content')
</div>

@stack('modals')
@stack('scripts')
</body>
</html>
