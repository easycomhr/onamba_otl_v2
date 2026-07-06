@extends('layouts.employee')

@section('title', __('ui.team_approval.title') . ' - OTMS')

@section('content')
<div class="sticky top-0 z-50 bg-white shadow-sm flex items-center p-4">
    <a href="{{ route('employee.dashboard') }}" class="absolute left-4 flex items-center text-gray-600 hover:text-blue-600 active:scale-95 transition-all p-2 -ml-2 rounded-lg">
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
    </a>
    <h1 class="text-[19px] sm:text-xl font-bold text-blue-600 uppercase w-full text-center tracking-wide">{{ __('ui.team_approval.title') }}</h1>
    <button onclick="window.location.reload()"
            class="absolute right-4 flex items-center text-gray-600 hover:text-blue-600 active:scale-95 transition-all p-2 -mr-2 rounded-lg"
            title="Reload">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
        </svg>
    </button>
</div>

<div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-6">
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- Type filter tabs --}}
    <div class="flex gap-2">
        <a href="{{ route('employee.team-approvals.index') }}"
           class="flex-1 text-center py-2 rounded-xl text-sm font-semibold border transition-colors
               {{ $type === null ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
            {{ __('ui.common.all') }}
        </a>
        <a href="{{ route('employee.team-approvals.index', ['type' => 'ot']) }}"
           class="flex-1 text-center py-2 rounded-xl text-sm font-semibold border transition-colors
               {{ $type === 'ot' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-blue-600 border-blue-200 hover:bg-blue-50' }}">
            OT
        </a>
        <!-- <a href="{{ route('employee.team-approvals.index', ['type' => 'leave']) }}"
           class="flex-1 text-center py-2 rounded-xl text-sm font-semibold border transition-colors
               {{ $type === 'leave' ? 'bg-green-600 text-white border-green-600' : 'bg-white text-green-600 border-green-200 hover:bg-green-50' }}">
            {{ __('ui.common.leave') }}
        </a> -->
    </div>

    {{-- OT Section --}}
    @if($type !== 'leave')
    <section>
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-bold text-gray-800 text-[16px]">{{ __('ui.team_approval.pending_ot') }}</h2>
            <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-full">{{ $pendingOtRequests->total() }}</span>
        </div>

        @if($pendingOtRequests->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-6 text-sm text-gray-400 text-center">
                {{ __('ui.team_approval.no_ot') }}
            </div>
        @else
            <div class="space-y-4">
                @foreach($pendingOtRequests as $ot)
                <a href="{{ route('employee.team-approvals.ot.show', $ot) }}"
                   class="block bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 relative overflow-hidden active:scale-[0.98] transition-transform">
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-blue-400 rounded-l-2xl"></div>
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-3 pl-2">
                        <span class="text-[17px] font-bold text-gray-800">#{{ $ot->code }}</span>
                        @if($ot->isOverdue())
                            <span class="bg-red-100 text-red-600 px-2 py-0.5 rounded-full text-[13px] font-bold">⏰ Quá hạn</span>
                        @else
                            <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-[14px] font-bold uppercase">{{ __('ui.status.pending') }}</span>
                        @endif
                    </div>
                    <div class="space-y-2.5 text-[16px] pl-2">
                        <div class="flex justify-between items-start gap-4">
                            <span class="text-gray-500 shrink-0">{{ __('ui.table.employee') }}:</span>
                            <span class="text-gray-800 font-semibold text-right">{{ $ot->employee->name }} <span class="text-gray-400 text-sm">({{ $ot->employee->employee_code }})</span></span>
                        </div>
                        <div class="flex justify-between items-start gap-4">
                            <span class="text-gray-500 shrink-0">{{ __('ui.ot.ot_date') }}:</span>
                            <span class="text-gray-800 font-semibold text-right">{{ $ot->ot_date?->format('d/m/Y') ?? '--/--/----' }}</span>
                        </div>
                        <div class="flex justify-between items-start gap-4">
                            <span class="text-gray-500 shrink-0">{{ __('ui.ot.hours_short') }}:</span>
                            <span class="text-gray-800 font-semibold text-right">{{ $ot->hours }} {{ __('ui.common.unit_hour') }}</span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $pendingOtRequests->links() }}
            </div>
        @endif
    </section>
    @endif

    {{-- Leave Section --}}
    <!-- @if($type !== 'ot')
    <section>
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-bold text-gray-800 text-[16px]">{{ __('ui.team_approval.pending_leave') }}</h2>
            <span class="bg-green-100 text-green-700 text-xs font-bold px-2.5 py-1 rounded-full">{{ $pendingLeaveRequests->total() }}</span>
        </div>

        @if($pendingLeaveRequests->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-6 text-sm text-gray-400 text-center">
                {{ __('ui.team_approval.no_leave') }}
            </div>
        @else
            <div class="space-y-4">
                @foreach($pendingLeaveRequests as $leave)
                <a href="{{ route('employee.team-approvals.leave.show', $leave) }}"
                   class="block bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 relative overflow-hidden active:scale-[0.98] transition-transform">
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-green-400 rounded-l-2xl"></div>
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-3 pl-2">
                        <span class="text-[17px] font-bold text-gray-800">#{{ $leave->code }}</span>
                        @if($leave->isOverdue())
                            <span class="bg-red-100 text-red-600 px-2 py-0.5 rounded-full text-[13px] font-bold">⏰ Quá hạn</span>
                        @else
                            <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-[14px] font-bold uppercase">{{ __('ui.status.pending') }}</span>
                        @endif
                    </div>
                    <div class="space-y-2.5 text-[16px] pl-2">
                        <div class="flex justify-between items-start gap-4">
                            <span class="text-gray-500 shrink-0">{{ __('ui.table.employee') }}:</span>
                            <span class="text-gray-800 font-semibold text-right">{{ $leave->employee->name }} <span class="text-gray-400 text-sm">({{ $leave->employee->employee_code }})</span></span>
                        </div>
                        <div class="flex justify-between items-start gap-4">
                            <span class="text-gray-500 shrink-0">{{ __('ui.leave.from_date') }}:</span>
                            <span class="text-gray-800 font-semibold text-right">{{ $leave->from_date?->format('d/m/Y') ?? '--/--/----' }}</span>
                        </div>
                        <div class="flex justify-between items-start gap-4">
                            <span class="text-gray-500 shrink-0">{{ __('ui.leave.to_date') }}:</span>
                            <span class="text-gray-800 font-semibold text-right">{{ $leave->to_date?->format('d/m/Y') ?? '--/--/----' }}</span>
                        </div>
                        <div class="flex justify-between items-start gap-4">
                            <span class="text-gray-500 shrink-0">{{ __('ui.leave.total_days') }}:</span>
                            <span class="text-green-600 font-bold text-right">{{ $leave->days }} {{ __('ui.common.unit_day') }}</span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $pendingLeaveRequests->links() }}
            </div>
        @endif
    </section>
    @endif -->
</div>
@endsection
