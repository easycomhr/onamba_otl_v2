@extends('layouts.admin')

@section('title', __('ui.nav.admin_dashboard') . ' - OTMS Manager')
@section('page_title', __('ui.nav.admin_dashboard'))
@push('cdn_scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
<div class="space-y-6 md:space-y-8">

    <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 uppercase tracking-tight hidden md:block">
        {{ __('ui.nav.admin_dashboard') }}
    </h1>

    <div class="rounded-2xl border border-blue-100 bg-white px-5 py-4 shadow-sm flex items-center gap-3">
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 flex-shrink-0">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2.25M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-gray-500">{{ __('ui.dashboard.system_time') }}</p>
            <p id="admin-clock-display" class="mt-1 text-base font-semibold text-gray-800 md:text-lg">--/--/---- --:--:--</p>
        </div>
    </div>

    {{-- Alert Cards --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 md:gap-6">
        <div class="bg-blue-50 border-2 border-blue-200 rounded-2xl p-5 md:p-6 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5 hover:shadow-md transition-all">
            <div class="flex items-start">
                <div class="bg-blue-200 p-2.5 rounded-full mr-4 text-blue-700 flex-shrink-0">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-blue-800 uppercase tracking-wide">{{ __('ui.dashboard.pending_ot_requests') }}</h3>
                    <p class="text-blue-900 text-[15px] mt-1">
                        <strong class="text-blue-700 text-xl mx-1">{{ $stats['ot_pending'] }}</strong> {{ __('ui.common.unit_request') }}
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.approvals.ot.index') }}"
               class="w-full sm:w-auto text-center px-6 py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[15px] uppercase rounded-xl shadow-md flex-shrink-0 whitespace-nowrap focus:outline-none focus:ring-4 focus:ring-blue-200 transition-all">
                {{ __('ui.approval.approve_ot') }}
            </a>
        </div>

        <div class="bg-green-50 border-2 border-green-200 rounded-2xl p-5 md:p-6 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5 hover:shadow-md transition-all">
            <div class="flex items-start">
                <div class="bg-green-200 p-2.5 rounded-full mr-4 text-green-700 flex-shrink-0">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-green-800 uppercase tracking-wide">{{ __('ui.dashboard.pending_leave_requests') }}</h3>
                    <p class="text-green-900 text-[15px] mt-1">
                        <strong class="text-green-700 text-xl mx-1">{{ $stats['leave_pending'] }}</strong> {{ __('ui.common.unit_request') }}
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.approvals.leave.index') }}"
               class="w-full sm:w-auto text-center px-6 py-3.5 bg-green-600 hover:bg-green-700 text-white font-bold text-[15px] uppercase rounded-xl shadow-md flex-shrink-0 whitespace-nowrap focus:outline-none focus:ring-4 focus:ring-green-200 transition-all">
                {{ __('ui.approval.approve_leave') }}
            </a>
        </div>
    </div>

    {{-- Absent today --}}
    <div class="w-full bg-orange-50 border-2 border-orange-200 rounded-2xl p-4 md:p-5 shadow-sm flex items-start sm:items-center">
        <div class="bg-orange-200 p-2.5 rounded-full mr-4 text-orange-700 flex-shrink-0">
            <svg class="w-6 h-6 md:w-7 md:h-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
            </svg>
        </div>
        <p class="text-orange-900 text-[15px] md:text-[16px] leading-relaxed">
            <span class="font-extrabold uppercase text-orange-800 mr-2 border-b-2 border-orange-300">{{ __('ui.dashboard.today_absent') }}:</span>
            <strong class="text-orange-700 text-lg mx-1">{{ $stats['absent_today'] }}</strong> {{ __('ui.table.employee') }}
        </p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
        <div class="bg-white p-5 md:p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center">
            <div class="bg-blue-50 p-3.5 rounded-xl mr-4 border border-blue-100 flex-shrink-0">
                <svg class="w-7 h-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-[13px] font-bold text-gray-500 uppercase tracking-wide">{{ __('ui.dashboard.approved_ot_hours') }}</p>
                <h4 class="text-2xl font-black text-blue-600 mt-1 leading-none">{{ $stats['approved_ot_hours'] }} <span class="text-[15px] font-bold text-gray-500">{{ __('ui.common.unit_hour') }}</span></h4>
            </div>
        </div>

        <div class="bg-white p-5 md:p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center">
            <div class="bg-orange-50 p-3.5 rounded-xl mr-4 border border-orange-100 flex-shrink-0">
                <svg class="w-7 h-7 text-orange-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
            </div>
            <div>
                <p class="text-[13px] font-bold text-gray-500 uppercase tracking-wide">{{ __('ui.dashboard.pending_ot_requests') }}</p>
                <h4 class="text-2xl font-black text-orange-500 mt-1 leading-none">{{ $stats['ot_pending'] }} <span class="text-[15px] font-bold text-gray-500">{{ __('ui.common.unit_request') }}</span></h4>
            </div>
        </div>

        <div class="bg-white p-5 md:p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center">
            <div class="bg-green-50 p-3.5 rounded-xl mr-4 border border-green-100 flex-shrink-0">
                <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                </svg>
            </div>
            <div>
                <p class="text-[13px] font-bold text-gray-500 uppercase tracking-wide">{{ __('ui.dashboard.approved_leave_days') }}</p>
                <h4 class="text-2xl font-black text-green-600 mt-1 leading-none">{{ $stats['approved_leave_days'] }} <span class="text-[15px] font-bold text-gray-500">{{ __('ui.common.unit_day') }}</span></h4>
            </div>
        </div>

        <div class="bg-white p-5 md:p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center">
            <div class="bg-orange-50 p-3.5 rounded-xl mr-4 border border-orange-100 flex-shrink-0">
                <svg class="w-7 h-7 text-orange-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
            </div>
            <div>
                <p class="text-[13px] font-bold text-gray-500 uppercase tracking-wide">{{ __('ui.dashboard.pending_leave_requests') }}</p>
                <h4 class="text-2xl font-black text-orange-500 mt-1 leading-none">{{ $stats['leave_pending'] }} <span class="text-[15px] font-bold text-gray-500">{{ __('ui.common.unit_request') }}</span></h4>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 md:gap-6 pb-12">
        <div class="bg-white p-5 md:p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col">
            <h3 class="text-[16px] font-bold text-gray-800 mb-6 flex items-center">
                <span class="w-2 h-6 bg-blue-500 rounded-full mr-3"></span>
                {{ __('ui.nav.reports_ot') }}
            </h3>
            <div class="relative flex-1 w-full min-h-[300px]">
                <canvas id="otChart"></canvas>
            </div>
        </div>

        <div class="bg-white p-5 md:p-6 rounded-2xl border border-gray-200 shadow-sm flex flex-col">
            <h3 class="text-[16px] font-bold text-gray-800 mb-6 flex items-center">
                <span class="w-2 h-6 bg-green-500 rounded-full mr-3"></span>
                {{ __('ui.nav.reports_leave') }}
            </h3>
            <div class="relative flex-1 w-full min-h-[300px]">
                <canvas id="leaveChart"></canvas>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.color = '#6b7280';

    const adminClockDisplay = document.getElementById('admin-clock-display');
    const weekdays = {{ Js::from([
        __('ui.weekday.sun'),
        __('ui.weekday.mon'),
        __('ui.weekday.tue'),
        __('ui.weekday.wed'),
        __('ui.weekday.thu'),
        __('ui.weekday.fri'),
        __('ui.weekday.sat'),
    ]) }};
    const pad = (value) => String(value).padStart(2, '0');

    const renderAdminClock = () => {
        if (!adminClockDisplay) {
            return;
        }

        const now = new Date();
        adminClockDisplay.textContent = `${weekdays[now.getDay()]}, ${pad(now.getDate())}/${pad(now.getMonth() + 1)}/${now.getFullYear()} - ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
    };

    renderAdminClock();
    setInterval(renderAdminClock, 1000);

    const otChartData = @json($otChart);
    const ctxOt = document.getElementById('otChart').getContext('2d');
    new Chart(ctxOt, {
        type: 'bar',
        data: {
            labels: otChartData.map(d => d.label),
            datasets: [{ label: @json(__('ui.dashboard.approved_ot_hours')), data: otChartData.map(d => d.hours), backgroundColor:'#3b82f6', hoverBackgroundColor:'#2563eb', borderRadius:4, barPercentage:0.6 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor:'#1f2937', padding:12,
                    callbacks: { title: ctx => ctx[0].label, label: ctx => ctx.parsed.y + ' ' + @json(__('ui.common.unit_hour')) }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color:'#f3f4f6', borderDash:[5,5] } }
            }
        }
    });

    const leaveChartData = @json($leaveChart);
    const ctxLeave = document.getElementById('leaveChart').getContext('2d');
    new Chart(ctxLeave, {
        type: 'doughnut',
        data: {
            labels: leaveChartData.map(d => d.label),
            datasets: [{ data: leaveChartData.map(d => d.count), backgroundColor:['#16a34a','#22c55e','#4ade80','#86efac'], borderColor:'#ffffff', borderWidth:2, hoverOffset:4 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout:'65%',
            plugins: {
                legend: { position:'right', labels: { padding:20, usePointStyle:true, pointStyle:'circle' } },
                tooltip: { backgroundColor:'#1f2937', padding:12, callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' ' + @json(__('ui.common.unit_request')) } }
            }
        }
    });
});
</script>
@endpush
