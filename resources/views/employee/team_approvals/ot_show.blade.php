@extends('layouts.employee')

@section('title', __('ui.ot.detail_title') . ' - OTMS')

@section('content')
<div class="sticky top-0 z-50 bg-white shadow-sm flex items-center p-4">
    <a href="{{ route('employee.dashboard') }}" class="absolute left-4 flex items-center text-gray-600 hover:text-blue-600 active:scale-95 transition-all p-2 -ml-2 rounded-lg">
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
    </a>
    <h1 class="text-[19px] sm:text-xl font-bold text-blue-600 uppercase w-full text-center tracking-wide">{{ __('ui.ot.detail_title') }}</h1>
</div>

<div class="flex-1 overflow-y-auto p-4 sm:p-5">
    @php
        $statusClasses = match ($otRequest->status) {
            \App\Models\OtRequest::STATUS_APPROVED => 'bg-green-100 text-green-700',
            \App\Models\OtRequest::STATUS_REJECTED => 'bg-red-100 text-red-700',
            default => 'bg-yellow-100 text-yellow-700',
        };
        $statusLabels = [
            \App\Models\OtRequest::STATUS_PENDING => __('ui.status.pending'),
            \App\Models\OtRequest::STATUS_APPROVED => __('ui.status.approved'),
            \App\Models\OtRequest::STATUS_REJECTED => __('ui.status.rejected'),
        ];
    @endphp

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
        <div class="flex items-center justify-between gap-3 mb-4 border-b border-gray-100 pb-3">
            <h2 class="text-lg font-bold text-gray-800">{{ $otRequest->code }}</h2>
            <span class="{{ $statusClasses }} px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                {{ $statusLabels[$otRequest->status] ?? $otRequest->status }}
            </span>
        </div>

        <div class="space-y-3 text-sm sm:text-base">
            <div class="flex justify-between gap-4">
                <span class="text-gray-500">{{ __('ui.table.employee') }}</span>
                <span class="font-semibold text-gray-800 text-right">{{ $otRequest->employee->name }} ({{ $otRequest->employee->employee_code }})</span>
            </div>
            <div class="flex justify-between gap-4">
                <span class="text-gray-500">{{ __('ui.ot.ot_date') }}</span>
                <span class="font-semibold text-gray-800 text-right">{{ $otRequest->ot_date?->format('d/m/Y') ?? '--/--/----' }}</span>
            </div>
            <div class="flex justify-between gap-4">
                <span class="text-gray-500">{{ __('ui.ot.hours_short') }}</span>
                <span class="font-semibold text-gray-800 text-right">{{ $otRequest->hours }} {{ __('ui.common.unit_hour') }}</span>
            </div>
            <div class="flex justify-between gap-4">
                <span class="text-gray-500">{{ __('ui.ot.created_at') }}</span>
                <span class="font-semibold text-gray-800 text-right">{{ $otRequest->created_at?->format('d/m/Y H:i') ?? '--/--/---- --:--' }}</span>
            </div>
            <div>
                <p class="text-gray-500 mb-1">{{ __('ui.ot.reason') }}</p>
                <p class="text-gray-800 leading-relaxed">{{ $otRequest->reason ?: '-' }}</p>
            </div>
            @if($otRequest->manager_note)
                <div>
                    <p class="text-gray-500 mb-1">{{ __('ui.ot.note') }}</p>
                    <p class="text-gray-800 leading-relaxed">{{ $otRequest->manager_note }}</p>
                </div>
            @endif
        </div>
    </div>

    @if($otRequest->status === \App\Models\OtRequest::STATUS_PENDING)
        @if($otRequest->isOverdue())
            {{-- Quá hạn: Approver không được duyệt, chỉ được gửi SOS --}}
            @if($otRequest->hasSos())
            <div class="bg-orange-50 border border-orange-200 rounded-2xl p-5">
                <div class="flex items-center gap-3 mb-1">
                    <span class="text-2xl">🆘</span>
                    <h3 class="text-base font-bold text-orange-700">{{ __('ui.sos.sent_at') }}</h3>
                </div>
                <p class="text-sm text-orange-600 ml-9">{{ $otRequest->sos_requested_at->format('d/m/Y H:i') }}</p>
                <p class="text-sm text-orange-500 mt-2 ml-9">{{ __('ui.sos.approver_sos_sent_note') }}</p>
            </div>
            @else
            <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-5 mb-4">
                <p class="text-sm text-yellow-700">{{ __('ui.sos.approver_overdue_notice', ['hours' => \App\Models\OtRequest::SOS_HOURS]) }}</p>
            </div>
            <form method="POST" action="{{ route('employee.team-approvals.ot.sos', $otRequest) }}" class="bg-white rounded-2xl shadow-sm border border-red-100 p-5">
                @csrf
                <h3 class="text-base font-bold text-red-700 mb-2">🆘 {{ __('ui.sos.approver_button') }}</h3>
                <p class="text-sm text-gray-500 mb-4">{{ __('ui.sos.approver_sos_hint') }}</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('ui.sos.reason_label') }} <span class="text-red-500">*</span></label>
                        <textarea name="sos_reason" rows="4" required maxlength="500"
                                  placeholder="{{ __('ui.sos.approver_reason_placeholder') }}"
                                  class="w-full rounded-xl border-gray-300 focus:border-red-500 focus:ring-red-500 resize-none">{{ old('sos_reason') }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-red-600 text-white font-bold py-3 rounded-xl hover:bg-red-700 transition-colors">
                        🆘 {{ __('ui.sos.submit') }}
                    </button>
                </div>
            </form>
            @endif
        @else
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <form method="POST" action="{{ route('employee.team-approvals.ot.approve', $otRequest) }}" class="bg-white rounded-2xl shadow-sm border border-blue-100 p-5">
                @csrf
                <h3 class="text-base font-bold text-blue-700 mb-4">{{ __('ui.approval.approve_ot') }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('ui.common.note') }}</label>
                        <textarea name="manager_note" rows="3" class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500" placeholder="{{ __('ui.common.note_optional') }}">{{ old('manager_note') }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition-colors">{{ __('ui.approval.approve_ot') }}</button>
                </div>
            </form>

            <form method="POST" action="{{ route('employee.team-approvals.ot.reject', $otRequest) }}" class="bg-white rounded-2xl shadow-sm border border-red-100 p-5">
                @csrf
                <h3 class="text-base font-bold text-red-700 mb-4">{{ __('ui.approval.reject_ot') }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('ui.status.rejected_reason') }}</label>
                        <textarea name="manager_note" rows="6" class="w-full rounded-xl border-gray-300 focus:border-red-500 focus:ring-red-500" placeholder="{{ __('ui.common.reject_reason_optional') }}">{{ old('manager_note') }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-red-600 text-white font-bold py-3 rounded-xl hover:bg-red-700 transition-colors">{{ __('ui.approval.reject_ot') }}</button>
                </div>
            </form>
        </div>
        @endif
    @endif
</div>
@endsection
