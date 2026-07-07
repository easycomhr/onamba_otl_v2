<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'OTMS Manager')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon_128x128.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    @stack('cdn_scripts')

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        blue:   { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af' },
                        green:  { 50:'#f0fdf4',100:'#dcfce3',200:'#bbf7d0',500:'#22c55e',600:'#16a34a',700:'#15803d',800:'#166534' },
                        orange: { 50:'#fff7ed',100:'#ffedd5',200:'#fed7aa',500:'#f97316',700:'#c2410c',800:'#9a3412' },
                        gray:   { 50:'#f9fafb',100:'#f3f4f6',200:'#e5e7eb',400:'#9ca3af',500:'#6b7280',800:'#1f2937',900:'#111827' },
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans text-[15px] text-gray-800 antialiased flex h-screen overflow-hidden">

{{-- ── Sidebar ── --}}
<aside id="sidebar"
       class="bg-white w-64 md:w-72 border-r border-gray-200 flex-shrink-0 flex flex-col
              fixed md:relative z-40 h-full transform -translate-x-full md:translate-x-0
              transition-transform duration-300 shadow-lg md:shadow-none">

    <div class="h-16 flex items-center px-6 border-b border-gray-200 flex-shrink-0">
        <img src="{{ asset('images/onamba_logo.png') }}" alt="Onamba" style="width:auto">
    </div>

    {{-- Helper: $allowedModules === null → full access; array → check key --}}
    @php
        $canSee = fn(string $mod) => $allowedModules === null || in_array($mod, $allowedModules);

    @endphp

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">

        <h3 class="px-3 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 mt-2">{{ __('ui.nav.dashboard') }}</h3>
        @if($canSee('dashboard'))
        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center px-3 py-2.5 rounded-r-lg font-medium transition-colors
                  {{ request()->routeIs('admin.dashboard') ? 'bg-gray-100 text-gray-900 border-l-4 border-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
            </svg>
            {{ __('ui.nav.admin_dashboard') }}
        </a>
        @endif

        @if($canSee('approvals.ot') || $canSee('approvals.leave'))
        <h3 class="px-3 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 mt-6 border-t border-gray-100 pt-4">{{ __('ui.menu.approvals_section') }}</h3>
        @if($canSee('approvals.ot'))
        <a href="{{ route('admin.approvals.ot.index') }}"
           class="flex items-center justify-between px-3 py-2.5 rounded-lg font-medium transition-colors
                  {{ request()->routeIs('admin.approvals.ot.*') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                </svg>
                {{ __('ui.nav.ot_approvals') }}
            </div>
            @if($sidebarOtPending > 0)
            <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2.5 py-0.5 rounded-full">{{ $sidebarOtPending }}</span>
            @endif
        </a>
        @endif
        @if($canSee('approvals.leave'))
        <a href="{{ route('admin.approvals.leave.index') }}"
           class="flex items-center justify-between px-3 py-2.5 rounded-lg font-medium transition-colors mb-4
                  {{ request()->routeIs('admin.approvals.leave.*') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                </svg>
                {{ __('ui.nav.leave_approvals') }}
            </div>
            @if($sidebarLeavePending > 0)
            <span class="bg-green-100 text-green-700 text-xs font-bold px-2.5 py-0.5 rounded-full">{{ $sidebarLeavePending }}</span>
            @endif
        </a>
        @endif
        @endif

        @if($canSee('register.ot') || $canSee('register.leave') || $canSee('import.ot') || $canSee('import.leave'))
        <h3 class="px-3 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 mt-6 border-t border-gray-100 pt-4">{{ __('ui.menu.register_import_section') }}</h3>
        @if($canSee('register.ot'))
        <a href="{{ route('admin.register.ot.create') }}"
           class="flex items-center px-3 py-2.5 rounded-lg font-medium transition-colors
                  {{ request()->routeIs('admin.register.ot.*') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ __('ui.nav.ot_register') }}
        </a>
        @endif
        @if($canSee('register.leave'))
        <a href="{{ route('admin.register.leave.create') }}"
           class="flex items-center px-3 py-2.5 rounded-lg font-medium transition-colors
                  {{ request()->routeIs('admin.register.leave.*') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ __('ui.nav.leave_register') }}
        </a>
        @endif
        @if($canSee('import.ot'))
        <a href="{{ route('admin.import.ot') }}"
           class="flex items-center px-3 py-2.5 rounded-lg font-medium transition-colors
                  {{ request()->routeIs('admin.import.ot*') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
            </svg>
            {{ __('ui.nav.import_ot') }}
        </a>
        @endif
        @if($canSee('import.leave'))
        <a href="{{ route('admin.import.leave') }}"
           class="flex items-center px-3 py-2.5 rounded-lg font-medium transition-colors mb-4
                  {{ request()->routeIs('admin.import.leave*') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
            </svg>
            {{ __('ui.nav.import_leave') }}
        </a>
        @endif
        @endif

        @if($canSee('salary'))
        <h3 class="px-3 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 mt-6 border-t border-gray-100 pt-4">{{ __('ui.menu.salary_section') }}</h3>
        <a href="{{ route('admin.salary.index') }}"
           class="flex items-center px-3 py-2.5 rounded-lg font-medium transition-colors
                  {{ request()->routeIs('admin.salary.index') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5 mr-3 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            {{ __('ui.nav.salary') }}
        </a>
        <a href="{{ route('admin.salary.import') }}"
           class="flex items-center px-3 py-2.5 rounded-lg font-medium transition-colors mb-4
                  {{ request()->routeIs('admin.salary.import*') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5 mr-3 text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
            </svg>
            {{ __('ui.nav.import_salary') }}
        </a>
        @endif

        @if($canSee('employees') || $canSee('teams') || $canSee('reports.ot') || $canSee('reports.leave') || $allowedModules === null)
        <h3 class="px-3 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 mt-6 border-t border-gray-100 pt-4">{{ __('ui.menu.system_reports_section') }}</h3>
        @if($canSee('employees'))
        <a href="{{ route('admin.employees.index') }}"
           class="flex items-center px-3 py-2.5 rounded-lg font-medium transition-colors
                  {{ request()->routeIs('admin.employees.*') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
            </svg>
            {{ __('ui.nav.employees') }}
        </a>
        @endif
        @if($canSee('teams'))
        <a href="{{ route('admin.teams.index') }}"
           class="flex items-center px-3 py-2.5 rounded-lg font-medium transition-colors
                  {{ request()->routeIs('admin.teams.*') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.742-.479 3 3 0 00-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.204-.576-5.963-1.584A6.062 6.062 0 016 18.75m12-6.75a3 3 0 11-6 0 3 3 0 016 0zm-9 3a3 3 0 11-6 0 3 3 0 016 0zm9 0a5.966 5.966 0 00-1.5-.189m-3 0a5.966 5.966 0 00-1.5.189m0 0a5.97 5.97 0 00-3.75 5.56m7.5 0a5.97 5.97 0 00-3.75-5.56"/>
            </svg>
            {{ __('ui.nav.teams') }}
        </a>
        @endif
        @if($canSee('reports.ot'))
        <a href="{{ route('admin.reports.ot') }}"
           class="flex items-center px-3 py-2.5 rounded-lg font-medium transition-colors
                  {{ request()->routeIs('admin.reports.ot*') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
            {{ __('ui.nav.reports_ot') }}
        </a>
        @endif
{{--        @if($canSee('reports.leave'))--}}
{{--        <a href="{{ route('admin.reports.leave') }}"--}}
{{--           class="flex items-center px-3 py-2.5 rounded-lg font-medium transition-colors--}}
{{--                  {{ request()->routeIs('admin.reports.leave*') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50' }}">--}}
{{--            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">--}}
{{--                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>--}}
{{--            </svg>--}}
{{--            {{ __('ui.nav.reports_leave') }}--}}
{{--        </a>--}}
{{--        @endif--}}
        {{-- Admin Roles: manager only --}}
        @if($allowedModules === null)
        <a href="{{ route('admin.admin-roles.index') }}"
           class="flex items-center px-3 py-2.5 rounded-lg font-medium transition-colors mb-4
                  {{ request()->routeIs('admin.admin-roles.*') ? 'bg-gray-100 text-gray-900 font-bold' : 'text-gray-600 hover:bg-gray-50' }}">
            <svg class="w-5 h-5 mr-3 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
            </svg>
            {{ __('ui.admin_role.title') }}
        </a>
        @endif
        @endif

    </nav>

    <div class="p-4 border-t border-gray-200 flex-shrink-0 bg-gray-50/50 space-y-3">
        @php($currentLocale = app()->getLocale())
        <div class="rounded-lg overflow-hidden flex border border-gray-200 text-xs font-bold">
            <a href="{{ route('locale.switch', ['locale' => 'vi']) }}"
               class="flex-1 text-center py-2 transition-colors {{ $currentLocale === 'vi' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                VI
            </a>
            <a href="{{ route('locale.switch', ['locale' => 'en']) }}"
               class="flex-1 text-center py-2 transition-colors border-x border-gray-200 {{ $currentLocale === 'en' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                EN
            </a>
            <a href="{{ route('locale.switch', ['locale' => 'ja']) }}"
               class="flex-1 text-center py-2 transition-colors {{ $currentLocale === 'ja' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                JP
            </a>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <input type="hidden" name="portal" value="admin">
            <button type="submit"
                    class="w-full flex items-center px-3 py-2.5 text-gray-600 hover:bg-red-50 hover:text-red-600 rounded-lg font-medium transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                </svg>
                {{ __('ui.nav.logout') }}
            </button>
        </form>
    </div>
</aside>

<div id="sidebarOverlay" onclick="toggleSidebar()"
     class="fixed inset-0 bg-black/50 z-30 hidden md:hidden transition-opacity"></div>

{{-- ── Main Content ── --}}
<main class="flex-1 flex flex-col h-full w-full overflow-hidden relative">

    {{-- Mobile topbar --}}
    <div class="md:hidden h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 flex-shrink-0 z-20 shadow-sm">
        <div class="flex items-center">
            <button onclick="toggleSidebar()" class="p-2 -ml-2 text-gray-600 hover:bg-gray-100 rounded-lg focus:outline-none">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>
            <h2 class="ml-2 text-lg font-bold text-gray-900 uppercase">@yield('page_title', __('ui.nav.dashboard'))</h2>
        </div>
        <div class="w-8 h-8 rounded-full bg-gray-900 text-white flex items-center justify-center font-bold text-sm">M</div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mx-4 mt-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 font-medium flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mx-4 mt-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 font-medium flex items-center">
            <svg class="w-5 h-5 mr-2 text-red-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="max-w-7xl mx-auto">
            @yield('content')
        </div>
    </div>
</main>

<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    function toggleSidebar() {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }
</script>
@stack('scripts')
</body>
</html>
