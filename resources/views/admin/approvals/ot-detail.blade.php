@extends('layouts.admin')

@section('title', __('ui.ot.detail_title'))
@section('page_title', __('ui.ot.detail_title'))

@section('content')
<nav class="mb-6">
    <a href="{{ route('admin.approvals.ot.index') }}" class="flex items-center text-gray-500 hover:text-blue-600 transition-colors w-fit">
        <svg class="w-6 h-6 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
        <span class="font-semibold">{{ __('ui.common.back') }}</span>
    </a>
</nav>

<div class="max-w-2xl mx-auto">
    <h1 class="text-xl font-bold text-blue-600 uppercase text-center mb-6 tracking-wide hidden md:block">
        {{ __('ui.ot.detail_title') }}
    </h1>

    <form id="otApprovalForm" method="POST" action="{{ route('admin.approvals.ot.approve', $otRequest->id) }}">
        @csrf

        {{-- Section 1: Thông tin từ nhân viên --}}
        <fieldset class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
            <div class="bg-gray-50 px-5 py-4 border-b border-gray-200 flex items-center">
                <div class="bg-gray-200 p-2 rounded-full mr-3 text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>
                <h2 class="text-[17px] font-bold text-gray-800 uppercase tracking-wide">1. {{ __('ui.table.employee') }}</h2>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-[15px] font-bold text-gray-500 mb-1.5">{{ __('ui.ot.request_code') }}</label>
                    <div class="bg-gray-100 min-h-[50px] px-4 py-3 flex items-center rounded-xl font-bold text-gray-900 border border-gray-200">
                        #{{ $otRequest->code }}
                    </div>
                </div>
                <div>
                    <label class="block text-[15px] font-bold text-gray-500 mb-1.5">{{ __('ui.ot.ot_date') }}</label>
                    <div class="bg-gray-100 min-h-[50px] px-4 py-3 flex items-center rounded-xl font-semibold text-gray-800 border border-gray-200">
                        {{ $otRequest->ot_date->format('d/m/Y') }}
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-[15px] font-bold text-gray-500 mb-1.5">{{ __('ui.table.employee') }}</label>
                    <div class="bg-gray-100 min-h-[50px] px-4 py-3 flex items-center rounded-xl border border-gray-200">
                        <span class="font-bold text-gray-900 mr-2">{{ $otRequest->employee->name }}</span>
                        <span class="text-gray-500 font-medium">({{ $otRequest->employee->employee_code }})</span>
                    </div>
                </div>
                <div>
                    <label class="block text-[15px] font-bold text-gray-500 mb-1.5">{{ __('ui.ot.registered_hours') }}</label>
                    <div class="bg-blue-50 min-h-[50px] px-4 py-3 flex items-center rounded-xl border border-blue-100">
                        <span class="text-xl font-bold text-blue-600 mr-1">{{ $otRequest->hours }}</span>
                        <span class="text-blue-700 font-medium">{{ __('ui.common.unit_hour') }}</span>
                    </div>
                </div>
                <div>
                    <label class="block text-[15px] font-bold text-gray-500 mb-1.5">{{ __('ui.status.all') }}</label>
                    <div class="min-h-[50px] px-4 py-3 flex items-center rounded-xl border font-bold
                        {{ $otRequest->status === 'approved' ? 'bg-green-50 border-green-200 text-green-700' : ($otRequest->status === 'rejected' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-yellow-50 border-yellow-200 text-yellow-700') }}">
                        {{ $otRequest->status_label }}
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-[15px] font-bold text-gray-500 mb-1.5">{{ __('ui.ot.content') }}</label>
                    <div class="bg-gray-100 min-h-[50px] px-4 py-3 rounded-xl font-medium text-gray-800 border border-gray-200 leading-relaxed">
                        {{ $otRequest->reason }}
                    </div>
                </div>
            </div>
        </fieldset>

        @if($otRequest->hasSos())
        {{-- SOS Info --}}
        <div class="bg-orange-50 border border-orange-200 rounded-2xl p-5 mb-6">
            <div class="flex items-center gap-2 mb-3">
                <span class="text-xl">🆘</span>
                <h2 class="text-[16px] font-bold text-orange-700 uppercase tracking-wide">{{ __('ui.sos.admin_section_title') }}</h2>
            </div>
            <div class="space-y-2 text-[15px]">
                <div class="flex justify-between gap-4">
                    <span class="text-orange-600 font-medium shrink-0">{{ __('ui.sos.sent_at') }}:</span>
                    <span class="text-orange-800 font-semibold text-right">{{ $otRequest->sos_requested_at->format('d/m/Y H:i') }}</span>
                </div>
                <div>
                    <span class="text-orange-600 font-medium">{{ __('ui.sos.reason_label') }}:</span>
                    <p class="mt-1 text-orange-800 font-medium leading-relaxed">{{ $otRequest->sos_reason }}</p>
                </div>
            </div>
        </div>
        @elseif($otRequest->isOverdue() && $otRequest->status === 'pending')
        {{-- Overdue warning --}}
        <div class="bg-red-50 border border-red-200 rounded-2xl p-5 mb-6">
            <p class="text-red-700 font-semibold text-[15px]">⚠️ {{ __('ui.sos.admin_overdue_warning', ['hours' => 32]) }}</p>
        </div>
        @endif

        @if($otRequest->status === 'pending' && (!$otRequest->isOverdue() || $otRequest->hasSos()))
        {{-- Section 2: Quyết định phê duyệt --}}
        <fieldset class="bg-white rounded-2xl shadow-md border-2 border-blue-200 mb-8 overflow-hidden">
            <div class="bg-blue-50 px-5 py-4 border-b border-blue-100 flex items-center">
                <div class="bg-blue-200 p-2 rounded-full mr-3 text-blue-700">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                    </svg>
                </div>
                <h2 class="text-[17px] font-bold text-blue-700 uppercase tracking-wide">2. {{ __('ui.approval.decision_section') }}</h2>
            </div>
            <div class="p-5 space-y-5">

                <div>
                    <label for="manager_note" class="block text-[16px] font-bold text-gray-800 mb-2">
                        {{ __('ui.common.note_optional') }}
                    </label>
                    <textarea id="manager_note" name="manager_note" rows="3" placeholder="{{ __('ui.common.note_optional') }}"
                              class="block w-full p-4 text-[16px] text-gray-800 bg-white border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-colors resize-none">{{ old('manager_note') }}</textarea>
                </div>
            </div>
        </fieldset>

        <div class="flex flex-col-reverse sm:flex-row gap-4">
            <button type="button" onclick="handleReject()"
                    class="flex-1 flex items-center justify-center min-h-[56px] bg-white border-2 border-red-500 text-red-600 hover:bg-red-50 font-bold text-lg uppercase rounded-xl transition-colors focus:outline-none focus:ring-4 focus:ring-red-100">
                <svg class="w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                {{ __('ui.common.reject') }}
            </button>
            <button type="button" id="approveBtn"
                    class="flex-1 flex items-center justify-center min-h-[56px] bg-green-600 text-white hover:bg-green-700 font-bold text-lg uppercase rounded-xl shadow-md transition-colors focus:outline-none focus:ring-4 focus:ring-green-200">
                <svg class="w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                {{ __('ui.common.approve') }}
            </button>
        </div>
        @else
        {{-- Read-only: đơn đã xử lý --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-5 py-4 border-b border-gray-200">
                <h2 class="text-[17px] font-bold text-gray-800 uppercase tracking-wide">{{ __('ui.approval.review_result') }}</h2>
            </div>
            <div class="p-5 space-y-4">
                @if($otRequest->status === 'approved')
                <div>
                    <label class="block text-[15px] font-bold text-gray-500 mb-1.5">{{ __('ui.ot.approved_hours') }}</label>
                    <div class="bg-blue-50 min-h-[50px] px-4 py-3 flex items-center rounded-xl border border-blue-100">
                        <span class="text-xl font-bold text-blue-600 mr-1">{{ $otRequest->approved_hours }}</span>
                        <span class="text-blue-700 font-medium">{{ __('ui.common.unit_hour') }}</span>
                    </div>
                </div>
                @endif
                @if($otRequest->manager_note)
                <div>
                    <label class="block text-[15px] font-bold text-gray-500 mb-1.5">{{ __('ui.ot.note') }}</label>
                    <div class="bg-gray-100 px-4 py-3 rounded-xl text-gray-800 border border-gray-200 leading-relaxed">{{ $otRequest->manager_note }}</div>
                </div>
                @endif
                @if($otRequest->approvedBy)
                <div>
                    <label class="block text-[15px] font-bold text-gray-500 mb-1.5">{{ __('ui.common.processing_by') }}</label>
                    <div class="bg-gray-100 min-h-[50px] px-4 py-3 flex items-center rounded-xl border border-gray-200 font-semibold text-gray-800">{{ $otRequest->approvedBy->name }}</div>
                </div>
                @endif
                <div>
                    <label class="block text-[15px] font-bold text-gray-500 mb-1.5">{{ __('ui.common.processing_time') }}</label>
                    <div class="bg-gray-100 min-h-[50px] px-4 py-3 flex items-center rounded-xl border border-gray-200 text-gray-800">
                        {{ ($otRequest->approved_at ?? $otRequest->rejected_at)?->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
        </div>
        @endif
    </form>
</div>
@endsection

@push('cdn_scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@push('scripts')
<script>
    document.getElementById('approveBtn').addEventListener('click', async function () {
        const hours = {{ $otRequest->hours }};
        const result = await Swal.fire({
            title: '{{ __("ui.approval.confirm_approve") }}',
            text: `{{ __("ui.ot.registered_hours") }}: ${hours} {{ __("ui.common.unit_hour") }}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '{{ __("ui.common.approve") }}',
            cancelButtonText: '{{ __("ui.common.close") }}',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280',
        });
        if (result.isConfirmed) {
            document.getElementById('otApprovalForm').submit();
        }
    });

    async function handleReject() {
        const noteEl = document.getElementById('manager_note');
        if (!noteEl.value.trim()) {
            await Swal.fire({
                icon: 'warning',
                text: @json(__('ui.approval.enter_reject_reason_first')),
            });
            noteEl.focus();
            return;
        }
        const result = await Swal.fire({
            title: '{{ __("ui.approval.confirm_reject") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __("ui.common.reject") }}',
            cancelButtonText: '{{ __("ui.common.close") }}',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
        });
        if (result.isConfirmed) {
            const form = document.getElementById('otApprovalForm');
            form.action = '{{ route("admin.approvals.ot.reject", $otRequest->id) }}';
            form.submit();
        }
    }
</script>
@endpush
