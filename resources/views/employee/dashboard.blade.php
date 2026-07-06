@extends('layouts.employee')

@section('title', __('ui.nav.dashboard') . ' - OTMS & LMS')

@section('content')
@php
    $otSummary = $otSummary ?? [
        'pending_count' => 0,
        'approved_count' => 0,
        'approved_hours_month' => 0,
    ];
    $leaveSummary = $leaveSummary ?? [
        'pending_count' => 0,
        'approved_count' => 0,
        'approved_days_year' => 0,
    ];
    $recentRequests = $recentRequests ?? collect();
    $approverData = $approverData ?? [
        'isTeamApprover' => false,
        'pendingOtRequests' => collect(),
        'pendingLeaveRequests' => collect(),
    ];
    $isApprover = $isApprover ?? false;
    $pendingApprovalCount = $pendingApprovalCount ?? 0;
@endphp

{{-- Header --}}
<div class="bg-blue-700 text-white rounded-b-[2.5rem] pt-8 pb-16 px-6 relative z-0 shadow-sm">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-blue-100 text-sm font-medium mb-1">{{ __('ui.dashboard.welcome') }}</p>
            <h1 class="text-[22px] sm:text-2xl font-bold uppercase tracking-wide leading-tight">
                {{ auth()->user()->name ?? 'User' }}
            </h1>
            <p class="text-blue-100 text-[13px] mt-2 bg-blue-800/60 inline-flex items-center px-3 py-1 rounded-full font-medium border border-blue-600/50">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/>
                </svg>
                {{ __('ui.employee.code_short') }}: {{ auth()->user()->employee_code ?? 'NV12345' }}
            </p>
            <div class="mt-3 inline-flex items-center rounded-2xl border border-white/15 bg-white/10 px-4 py-2 text-sm text-blue-50 shadow-sm backdrop-blur-sm">
                <svg class="mr-2 h-4 w-4 text-blue-100" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2.25M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-medium">{{ __('ui.dashboard.system_time') }}:</span>
                <span id="clock-display" class="ml-2 font-semibold tracking-wide">--/--/---- --:--:--</span>
            </div>
        </div>
        <div class="flex-shrink-0 bg-white/20 p-3.5 rounded-full backdrop-blur-sm border border-white/10 shadow-inner">
            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0021.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 003.065 7.097A9.716 9.716 0 0012 21.75a9.716 9.716 0 006.685-2.653zm-12.54-1.285A7.486 7.486 0 0112 15a7.486 7.486 0 015.855 2.812A8.224 8.224 0 0112 20.25a8.224 8.224 0 01-5.855-2.438zM15.75 9a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" clip-rule="evenodd"/>
            </svg>
        </div>
    </div>
</div>

{{-- Summary cards --}}
<div class="relative z-10 px-5 -mt-10">
    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-1">
            <div class="rounded-2xl shadow bg-blue-600 text-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-blue-100">{{ __('ui.dashboard.ot_this_month') }}</p>
                        <p class="text-3xl font-bold leading-none mt-2">
                            {{ $otSummary['approved_hours_month'] ?? 0 }}
                            <span class="text-base font-semibold text-blue-100">{{ __('ui.dashboard.approved_hours') }}</span>
                        </p>
                    </div>
                    <div class="rounded-2xl bg-white/15 p-3 border border-white/10">
                        <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2.25M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2 text-sm font-semibold">
                    <span class="rounded-full bg-yellow-400/90 px-3 py-1 text-yellow-950">
                        {{ __('ui.status.pending') }}: {{ $otSummary['pending_count'] ?? 0 }}
                    </span>
                    <span class="rounded-full bg-green-400/90 px-3 py-1 text-green-950">
                        {{ __('ui.status.approved') }}: {{ $otSummary['approved_count'] ?? 0 }}
                    </span>
                </div>
            </div>

            <!-- <div class="rounded-2xl shadow bg-green-600 text-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-green-100">{{ __('ui.dashboard.leave_this_year') }}</p>
                        <p class="text-3xl font-bold leading-none mt-2">
                            {{ $leaveSummary['approved_days_year'] ?? 0 }}
                            <span class="text-base font-semibold text-green-100">{{ __('ui.dashboard.approved_days') }}</span>
                        </p>
                    </div>
                    <div class="rounded-2xl bg-white/15 p-3 border border-white/10">
                        <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v2.25M15.75 3v2.25M3.75 8.25h16.5M4.5 6.75h15A2.25 2.25 0 0121.75 9v9.75A2.25 2.25 0 0119.5 21h-15a2.25 2.25 0 01-2.25-2.25V9A2.25 2.25 0 014.5 6.75z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2 text-sm font-semibold">
                    <span class="rounded-full bg-yellow-300/95 px-3 py-1 text-yellow-950">
                        {{ __('ui.status.pending') }}: {{ $leaveSummary['pending_count'] ?? 0 }}
                    </span>
                    <span class="rounded-full bg-emerald-300/95 px-3 py-1 text-emerald-950">
                        {{ __('ui.status.approved') }}: {{ $leaveSummary['approved_count'] ?? 0 }}
                    </span>
                </div>
            </div> -->
        </div>

        @if($isApprover)
        {{-- Team approval card --}}
        <div class="rounded-2xl shadow bg-blue-600 text-white p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-blue-100">{{ __('ui.dashboard.approver_panel') }}</p>
                    <p class="text-3xl font-bold leading-none mt-2">
                        {{ $pendingApprovalCount }}
                        <span class="text-base font-semibold text-blue-100">{{ __('ui.dashboard.pending_requests') }}</span>
                    </p>
                </div>
                <div class="rounded-2xl bg-white/15 p-3 border border-white/10">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('employee.team-approvals.index') }}"
                   class="inline-flex items-center rounded-full bg-white/20 px-4 py-1.5 text-sm font-semibold text-white border border-white/20 hover:bg-white/30 active:scale-[0.97] transition-all">
                    {{ __('ui.dashboard.view_list') }}
                    <svg class="ml-1.5 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </a>
            </div>
        </div>
        @endif

        {{-- Leave balance card --}}
        <!-- <div class="bg-white rounded-2xl shadow p-5 flex items-center border border-gray-100">
            <div class="bg-green-100 p-3.5 rounded-2xl mr-4 shadow-sm border border-green-50">
                <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-[13px] font-bold text-gray-500 uppercase tracking-wide">{{ __('ui.dashboard.annual_leave_balance') }}</p>
                <p class="text-green-600 text-[28px] leading-none font-bold mt-1.5">
                    {{ $leaveBalance ?? 0 }} <span class="text-[16px] font-semibold text-green-700">{{ __('ui.common.unit_day') }}</span>
                </p>
            </div>
        </div> -->
    </div>
</div>

{{-- Main menu --}}
<div class="flex-1 overflow-y-auto px-5 pt-8 pb-4">
    {{-- Recent requests --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-[13px] font-bold text-gray-500 uppercase tracking-wider flex items-center">
                <span class="w-1.5 h-4 bg-blue-600 rounded-full mr-2"></span>
                {{ __('ui.dashboard.recent_requests') }}
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
            @if($recentRequests->isEmpty())
                <div class="px-5 py-6 text-sm text-gray-500 text-center">
                    {{ __('ui.dashboard.no_requests') }}
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($recentRequests as $req)
                        @php
                            $isOt = ($req['type'] ?? null) === 'ot';
                            $status = $req['status'] ?? 'pending';
                            $formattedDate = !empty($req['date'])
                                ? \Illuminate\Support\Carbon::parse($req['date'])->format('d/m/Y')
                                : '--/--/----';
                            $statusClasses = match ($status) {
                                'approved' => 'bg-green-100 text-green-700',
                                'rejected' => 'bg-red-100 text-red-700',
                                default => 'bg-yellow-100 text-yellow-700',
                            };
                            $statusLabels = [
                                'pending'  => __('ui.status.pending'),
                                'approved' => __('ui.status.approved'),
                                'rejected' => __('ui.status.rejected'),
                            ];
                        @endphp
                        @continue(!$isOt)
                        <div class="px-5 py-4 flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $isOt ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                                        {{ $isOt ? 'OT' : 'Leave' }}
                                    </span>
                                    <p class="text-sm font-bold text-gray-800 truncate">{{ $req['code'] ?? '-' }}</p>
                                </div>
                                <p class="text-sm text-gray-600">
                                    {{ $formattedDate }}
                                </p>
                                <p class="text-sm text-gray-500 mt-1">{{ $req['meta'] ?? '' }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold {{ $statusClasses }}">
                                {{ $statusLabels[$status] ?? ucfirst($status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if($approverData['isTeamApprover'])
    <div class="mb-8">
        <h2 class="text-[13px] font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center">
            <span class="w-1.5 h-4 bg-indigo-600 rounded-full mr-2"></span>
            {{ __('ui.dashboard.team_requests') }}
        </h2>

        {{-- Summary cards (mobile-first, tap to go to full list) --}}
        <div class="grid grid-cols-2 gap-3 mb-4">
            <a href="{{ route('employee.team-approvals.index', ['type' => 'ot']) }}"
               class="bg-white rounded-2xl border border-blue-100 shadow-sm p-4 flex flex-col items-center gap-2 active:bg-blue-50 transition-colors">
                <span class="text-3xl font-extrabold text-blue-600">{{ $approverData['pendingOtRequests']->count() }}</span>
                <span class="text-xs font-semibold text-gray-500 text-center leading-tight">{{ __('ui.dashboard.pending_ot') }}</span>
                @if($approverData['pendingOtRequests']->count() > 0)
                <span class="text-[11px] text-blue-500 font-medium">{{ __('ui.dashboard.tap_to_review') }} →</span>
                @endif
            </a>
{{--            <a href="{{ route('employee.team-approvals.index', ['type' => 'leave']) }}"--}}
{{--               class="bg-white rounded-2xl border border-green-100 shadow-sm p-4 flex flex-col items-center gap-2 active:bg-green-50 transition-colors">--}}
{{--                <span class="text-3xl font-extrabold text-green-600">{{ $approverData['pendingLeaveRequests']->count() }}</span>--}}
{{--                <span class="text-xs font-semibold text-gray-500 text-center leading-tight">{{ __('ui.dashboard.pending_leave') }}</span>--}}
{{--                @if($approverData['pendingLeaveRequests']->count() > 0)--}}
{{--                <span class="text-[11px] text-green-500 font-medium">{{ __('ui.dashboard.tap_to_review') }} →</span>--}}
{{--                @endif--}}
{{--            </a>--}}
        </div>

        {{-- Quick list (most recent 3 items of each, desktop gets full table via team-approvals/index) --}}
        @if($approverData['pendingOtRequests']->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm mb-3 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">{{ __('ui.dashboard.pending_ot') }}</h3>
                @if($approverData['pendingOtRequests']->count() > 3)
                <a href="{{ route('employee.team-approvals.index') }}" class="text-xs text-blue-600 font-semibold">{{ __('ui.dashboard.view_all') }} ({{ $approverData['pendingOtRequests']->count() }})</a>
                @endif
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($approverData['pendingOtRequests']->take(3) as $ot)
                <a href="{{ route('employee.team-approvals.ot.show', $ot) }}"
                   class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 active:bg-gray-100 transition-colors">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $ot->employee->name }}</p>
                        <p class="text-xs text-gray-500">{{ $ot->code }} · {{ $ot->ot_date?->format('d/m/Y') }} · {{ $ot->hours }}h</p>
                    </div>
                    @if($ot->isOverdue())
                    <span class="shrink-0 ml-2 text-xs font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full">{{ __('ui.dashboard.overdue') }}</span>
                    @else
                    <svg class="shrink-0 ml-2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
        @endif

{{--        @if($approverData['pendingLeaveRequests']->isNotEmpty())--}}
{{--        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">--}}
{{--            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">--}}
{{--                <h3 class="text-sm font-semibold text-gray-700">{{ __('ui.dashboard.pending_leave') }}</h3>--}}
{{--                @if($approverData['pendingLeaveRequests']->count() > 3)--}}
{{--                <a href="{{ route('employee.team-approvals.index') }}" class="text-xs text-green-600 font-semibold">{{ __('ui.dashboard.view_all') }} ({{ $approverData['pendingLeaveRequests']->count() }})</a>--}}
{{--                @endif--}}
{{--            </div>--}}
{{--            <div class="divide-y divide-gray-100">--}}
{{--                @foreach($approverData['pendingLeaveRequests']->take(3) as $leave)--}}
{{--                <a href="{{ route('employee.team-approvals.leave.show', $leave) }}"--}}
{{--                   class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 active:bg-gray-100 transition-colors">--}}
{{--                    <div class="min-w-0">--}}
{{--                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $leave->employee->name }}</p>--}}
{{--                        <p class="text-xs text-gray-500">{{ $leave->code }} · {{ $leave->from_date?->format('d/m/Y') }} · {{ $leave->days }} {{ __('ui.common.unit_day') }}</p>--}}
{{--                    </div>--}}
{{--                    @if($leave->isOverdue())--}}
{{--                    <span class="shrink-0 ml-2 text-xs font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full">{{ __('ui.dashboard.overdue') }}</span>--}}
{{--                    @else--}}
{{--                    <svg class="shrink-0 ml-2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>--}}
{{--                    @endif--}}
{{--                </a>--}}
{{--                @endforeach--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        @endif--}}

{{--        @if($approverData['pendingOtRequests']->isEmpty() && $approverData['pendingLeaveRequests']->isEmpty())--}}
{{--        <p class="text-sm text-gray-400 text-center py-4">{{ __('ui.dashboard.no_pending_requests') }}</p>--}}
{{--        @endif--}}
    </div>
    @endif

    {{-- OT Section --}}
    <div class="mb-8">
        <h2 class="text-[13px] font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center">
            <span class="w-1.5 h-4 bg-blue-600 rounded-full mr-2"></span>
            {{ __('ui.dashboard.ot_management') }}
        </h2>
        <div class="space-y-3.5">
            <a href="{{ route('employee.ot.create') }}"
               class="flex items-center min-h-[72px] bg-blue-600 text-white rounded-[1.25rem] shadow-md px-5 active:bg-blue-700 active:scale-[0.98] transition-all">
                <div class="bg-white/25 p-2.5 rounded-xl border border-white/10">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </div>
                <span class="text-[17px] font-bold ml-4 uppercase tracking-wide">{{ __('ui.dashboard.register_ot') }}</span>
            </a>
            <a href="{{ route('employee.ot.index') }}"
               class="flex items-center min-h-[72px] bg-white text-gray-800 border-2 border-gray-200 hover:border-blue-300 rounded-[1.25rem] shadow-sm px-5 active:bg-blue-50 active:scale-[0.98] transition-all">
                <div class="bg-blue-50 p-2.5 rounded-xl">
                    <svg class="w-7 h-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                    </svg>
                </div>
                <span class="text-[16px] font-bold ml-4 uppercase">{{ __('ui.dashboard.ot_history') }}</span>
            </a>
        </div>
    </div>

    {{-- Leave Section --}}
    <div class="mb-8">
        <h2 class="text-[13px] font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center">
            <span class="w-1.5 h-4 bg-green-600 rounded-full mr-2"></span>
            {{ __('ui.dashboard.leave_management') }}
        </h2>
        <div class="space-y-3.5">
            <a href="{{ route('employee.leave.create') }}"
               class="flex items-center min-h-[72px] bg-green-600 text-white rounded-[1.25rem] shadow-md px-5 active:bg-green-700 active:scale-[0.98] transition-all">
                <div class="bg-white/25 p-2.5 rounded-xl border border-white/10">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-[17px] font-bold ml-4 uppercase tracking-wide">{{ __('ui.dashboard.register_leave') }}</span>
            </a>
            <a href="{{ route('employee.leave.index') }}"
               class="flex items-center min-h-[72px] bg-white text-gray-800 border-2 border-gray-200 hover:border-green-300 rounded-[1.25rem] shadow-sm px-5 active:bg-green-50 active:scale-[0.98] transition-all">
                <div class="bg-green-50 p-2.5 rounded-xl">
                    <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                    </svg>
                </div>
                <span class="text-[16px] font-bold ml-4 uppercase">{{ __('ui.dashboard.leave_history') }}</span>
            </a>
        </div>
    </div>

    {{-- Salary Section --}}
{{--    <div class="mb-8">--}}
{{--        <h2 class="text-[13px] font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center">--}}
{{--            <span class="w-1.5 h-4 bg-purple-600 rounded-full mr-2"></span>--}}
{{--            {{ __('ui.dashboard.salary_management') }}--}}
{{--        </h2>--}}
{{--        <div class="space-y-3.5">--}}
{{--            <a href="{{ route('employee.salary.index') }}"--}}
{{--               class="flex items-center min-h-[72px] bg-purple-600 text-white rounded-[1.25rem] shadow-md px-5 active:bg-purple-700 active:scale-[0.98] transition-all">--}}
{{--                <div class="bg-white/25 p-2.5 rounded-xl border border-white/10">--}}
{{--                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">--}}
{{--                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>--}}
{{--                    </svg>--}}
{{--                </div>--}}
{{--                <span class="text-[17px] font-bold ml-4 uppercase tracking-wide">{{ __('ui.dashboard.salary_history') }}</span>--}}
{{--            </a>--}}
{{--        </div>--}}
{{--    </div>--}}

    {{-- Footer menu --}}
    <div class="mt-4 pt-6 border-t border-gray-200 space-y-3.5">
        <a href="{{ route('employee.profile') }}"
           class="flex items-center min-h-[64px] bg-white text-gray-700 border-2 border-gray-200 hover:bg-gray-50 rounded-xl px-5 active:scale-[0.98] transition-all">
            <svg class="w-6 h-6 text-gray-500 mr-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
            </svg>
            <span class="text-[16px] font-bold uppercase">{{ __('ui.nav.profile') }}</span>
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center justify-center min-h-[64px] bg-red-50 border-2 border-red-500 text-red-600 rounded-xl hover:bg-red-100 active:scale-[0.98] transition-all">
                <svg class="w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                </svg>
                <span class="text-[16px] font-bold uppercase tracking-wide">{{ __('ui.nav.logout') }}</span>
            </button>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const clockDisplay = document.getElementById('clock-display');

    if (!clockDisplay) {
        return;
    }

    const weekdays = {{ Js::from([__('ui.weekday.sun'), __('ui.weekday.mon'), __('ui.weekday.tue'), __('ui.weekday.wed'), __('ui.weekday.thu'), __('ui.weekday.fri'), __('ui.weekday.sat')]) }};

    const pad = (value) => String(value).padStart(2, '0');

    const renderClock = () => {
        const now = new Date();
        const formatted = `${weekdays[now.getDay()]}, ${pad(now.getDate())}/${pad(now.getMonth() + 1)}/${now.getFullYear()} - ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        clockDisplay.textContent = formatted;
    };

    renderClock();
    setInterval(renderClock, 1000);
});
</script>
@endpush
