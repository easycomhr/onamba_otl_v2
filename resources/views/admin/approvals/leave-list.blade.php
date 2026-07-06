@extends('layouts.admin')

@section('title', __('ui.approval.leave_list_title'))
@section('page_title', __('ui.nav.leave_approvals'))

@section('content')
<nav class="mb-6">
    <a href="{{ route('admin.dashboard') }}" class="flex items-center text-gray-500 hover:text-green-600 transition-colors w-fit">
        <svg class="w-6 h-6 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
        <span class="font-semibold">{{ __('ui.common.back_to_dashboard') }}</span>
    </a>
</nav>

<h1 class="text-xl font-bold text-gray-800 uppercase mb-6 hidden md:block">{{ __('ui.approval.leave_list_title') }}</h1>

<div class="mb-6 flex items-center text-gray-600">
    <svg class="w-5 h-5 mr-2 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
    </svg>
    <span><strong class="text-green-600">{{ $leaveRequests->where('status', 'pending')->count() }}</strong> {{ __('ui.common.unit_request') }}</span>
</div>

{{-- Filter bar --}}
<form method="GET" action="{{ route('admin.approvals.leave.index') }}" class="mb-5 flex flex-wrap gap-3">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('ui.employee.search_placeholder') }}"
           class="flex-1 min-w-[200px] px-4 py-2.5 border border-gray-300 rounded-xl text-[15px] focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
    <select name="leave_type" class="px-4 py-2.5 border border-gray-300 rounded-xl text-[15px] focus:outline-none focus:ring-2 focus:ring-green-500">
        <option value="">{{ __('ui.leave.type_short') }}</option>
        @foreach($leaveTypes as $value => $label)
            <option value="{{ $value }}" {{ request('leave_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <select name="status" class="px-4 py-2.5 border border-gray-300 rounded-xl text-[15px] focus:outline-none focus:ring-2 focus:ring-green-500">
        <option value="">{{ __('ui.status.all') }}</option>
        <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>{{ __('ui.status.pending') }}</option>
        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('ui.status.approved') }}</option>
        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('ui.status.rejected') }}</option>
    </select>
    <button type="submit" class="px-5 py-2.5 bg-green-600 text-white font-bold rounded-xl hover:bg-green-700 transition-colors text-[15px]">{{ __('ui.common.filter') }}</button>
</form>

<div class="md:bg-white md:rounded-2xl md:shadow-sm md:border md:border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead class="hidden md:table-header-group bg-gray-50 border-b border-gray-200">
        <tr>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm">{{ __('ui.ot.request_code') }}</th>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm">{{ __('ui.table.employee') }}</th>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm">{{ __('ui.leave.type_short') }}</th>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm">{{ __('ui.table.leave_date') }}</th>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm text-center">{{ __('ui.leave.total_days') }}</th>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm text-center">{{ __('ui.common.action') }}</th>
        </tr>
        </thead>
        <tbody class="block md:table-row-group">

        @forelse($leaveRequests as $row)
        <tr class="block md:table-row bg-white rounded-2xl shadow-sm mb-5 md:mb-0 md:rounded-none md:shadow-none md:border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="flex justify-between md:table-cell px-5 py-3 md:px-6 md:py-4 border-b border-gray-50 md:border-none">
                <span class="md:hidden font-medium text-gray-500">{{ __('ui.ot.request_code') }}:</span>
                <span class="font-bold text-gray-800 md:text-green-600">#{{ $row->code }}</span>
            </td>
            <td class="flex justify-between items-center md:table-cell px-5 py-3 md:px-6 md:py-4 border-b border-gray-50 md:border-none">
                <span class="md:hidden font-medium text-gray-500">{{ __('ui.table.employee') }}:</span>
                <div class="text-right md:text-left">
                    <p class="font-bold text-gray-800">{{ $row->employee->name }}</p>
                    <p class="text-sm text-gray-500">{{ $row->employee->employee_code }}</p>
                </div>
            </td>
            <td class="flex justify-between md:table-cell px-5 py-3 md:px-6 md:py-4 border-b border-gray-50 md:border-none">
                <span class="md:hidden font-medium text-gray-500">{{ __('ui.leave.type_short') }}:</span>
                <span class="inline-flex items-center px-3 py-1 bg-green-50 text-green-700 font-bold rounded-lg text-sm">{{ $row->leave_type_label }}</span>
            </td>
            <td class="flex justify-between md:table-cell px-5 py-3 md:px-6 md:py-4 border-b border-gray-50 md:border-none">
                <span class="md:hidden font-medium text-gray-500">{{ __('ui.table.leave_date') }}:</span>
                <span class="font-semibold text-gray-800 text-sm text-right md:text-left">
                    {{ $row->from_date->format('d/m/Y') }} → {{ $row->to_date->format('d/m/Y') }}
                </span>
            </td>
            <td class="flex justify-between md:table-cell px-5 py-3 md:px-6 md:py-4 border-b border-gray-50 md:border-none md:text-center">
                <span class="md:hidden font-medium text-gray-500">{{ __('ui.leave.total_days') }}:</span>
                <span class="inline-flex items-center justify-center px-3 py-1 bg-green-50 text-green-700 font-bold rounded-lg">{{ $row->days }} {{ __('ui.common.unit_day') }}</span>
            </td>
            <td class="block md:table-cell px-5 py-4 md:px-6 bg-gray-50/50 md:bg-transparent border-t md:border-none border-gray-100">
                @if($row->status === 'pending')
                    @if($row->isOverdue())
                    {{-- Overdue: ẩn Duyệt/Từ chối, chỉ hiện nút Cập nhật để vào detail xử lý SOS --}}
                    <a href="{{ route('admin.approvals.leave.show', $row->id) }}"
                       class="inline-flex items-center gap-1.5 px-2.5 py-2 rounded-xl font-bold text-sm transition-colors
                              {{ $row->hasSos() ? 'bg-orange-500 text-white hover:bg-orange-600' : 'bg-orange-50 text-orange-700 border border-orange-300 hover:bg-orange-100' }}">
                        <span>🆘</span>
                        <span>{{ __('ui.common.update') }}</span>
                    </a>
                    @else
                    {{-- Chưa overdue: hiện đủ 3 nút --}}
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('admin.approvals.leave.show', $row->id) }}"
                           class="flex items-center justify-center gap-2 min-h-[40px] px-3 py-2 border border-gray-300 text-gray-600 hover:bg-gray-100 hover:text-green-600 rounded-xl font-semibold text-sm transition-colors">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>{{ __('ui.common.view_detail') }}</span>
                        </a>
                        <button type="button" onclick="openConfirmModal('approve', '{{ route('admin.approvals.leave.approve', $row->id) }}')"
                                class="flex items-center justify-center gap-2 min-h-[40px] px-3 py-2 bg-green-50 text-green-700 border border-green-200 hover:bg-green-600 hover:text-white rounded-xl font-bold text-sm transition-colors">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            <span>{{ __('ui.common.approve') }}</span>
                        </button>
                        <button type="button" onclick="openConfirmModal('reject', '{{ route('admin.approvals.leave.reject', $row->id) }}')"
                                class="flex items-center justify-center gap-2 min-h-[40px] px-3 py-2 bg-red-50 text-red-700 border border-red-200 hover:bg-red-600 hover:text-white rounded-xl font-bold text-sm transition-colors">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span>{{ __('ui.common.reject') }}</span>
                        </button>
                    </div>
                    @endif
                @else
                <div class="flex flex-col items-start gap-1.5">
                    <span @class([
                        'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold',
                        'bg-green-100 text-green-700' => $row->status === 'approved',
                        'bg-red-100 text-red-700'     => $row->status === 'rejected',
                    ])>{{ $row->status_label }}</span>
                    <a href="{{ route('admin.approvals.leave.show', $row->id) }}"
                       class="inline-flex items-center gap-1.5 px-2.5 py-1.5 border border-gray-300 text-gray-600 hover:bg-gray-100 hover:text-green-600 rounded-lg font-semibold text-xs transition-colors">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ __('ui.common.view_detail') }}</span>
                    </a>
                </div>
                @endif
            </td>
        </tr>
        @empty
        <tr class="block md:table-row">
            <td colspan="6" class="px-6 py-10 text-center text-gray-400">{{ __('ui.report.no_data') }}</td>
        </tr>
        @endforelse

        </tbody>
    </table>
    </div>
</div>

{{-- Pagination --}}
@if($leaveRequests->hasPages())
<div class="mt-4">{{ $leaveRequests->links() }}</div>
@endif

{{-- Confirm Modal (Approve / Reject) --}}
<div id="confirmModal" class="fixed inset-0 bg-black/60 z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4">
    <div id="confirmCard" class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-transform duration-300">
        <div id="confirmHeader" class="px-6 py-4 border-b flex items-center justify-between">
            <div class="flex items-center" id="confirmTitleWrap">
                <svg id="confirmIcon" class="w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"></svg>
                <h3 id="confirmTitle" class="text-lg font-bold uppercase"></h3>
            </div>
            <button type="button" onclick="closeConfirmModal()" id="confirmCloseBtn" class="focus:outline-none">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <form id="confirmForm" method="POST">
                @csrf
                <div class="mb-6">
                    <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.common.note_optional') }}</label>
                    <textarea id="confirmNote" name="manager_note" rows="3" placeholder="{{ __('ui.common.note_optional') }}"
                              class="block w-full p-3 text-[15px] text-gray-800 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 resize-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" onclick="closeConfirmModal()"
                            class="w-full min-h-[48px] bg-white border-2 border-gray-300 text-gray-600 font-bold uppercase rounded-xl hover:bg-gray-50">{{ __('ui.common.cancel') }}</button>
                    <button type="submit" id="confirmSubmitBtn"
                            class="w-full min-h-[48px] text-white font-bold uppercase rounded-xl shadow-md">{{ __('ui.common.confirm') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const elModal = document.getElementById('confirmModal');
    const elCard  = document.getElementById('confirmCard');

    const config = {
        approve: {
            headerBg:    'bg-green-50 border-green-100',
            titleColor:  'text-green-700',
            closeColor:  'text-green-600 hover:text-green-800',
            ringColor:   'focus:ring-green-500',
            btnColor:    'bg-green-600 hover:bg-green-700',
            title:       @json(__('ui.approval.approve_leave')),
            icon:        '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>',
        },
        reject: {
            headerBg:    'bg-red-50 border-red-100',
            titleColor:  'text-red-700',
            closeColor:  'text-red-600 hover:text-red-800',
            ringColor:   'focus:ring-red-500',
            btnColor:    'bg-red-600 hover:bg-red-700',
            title:       @json(__('ui.approval.reject_leave')),
            icon:        '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
        },
    };

    function openConfirmModal(action, formAction) {
        const c = config[action];
        const header      = document.getElementById('confirmHeader');
        const titleWrap   = document.getElementById('confirmTitleWrap');
        const icon        = document.getElementById('confirmIcon');
        const title       = document.getElementById('confirmTitle');
        const closeBtn    = document.getElementById('confirmCloseBtn');
        const submitBtn   = document.getElementById('confirmSubmitBtn');
        const noteInput   = document.getElementById('confirmNote');

        // Reset classes
        header.className    = 'px-6 py-4 border-b flex items-center justify-between ' + c.headerBg;
        titleWrap.className = 'flex items-center ' + c.titleColor;
        closeBtn.className  = 'focus:outline-none ' + c.closeColor;
        submitBtn.className = 'w-full min-h-[48px] text-white font-bold uppercase rounded-xl shadow-md ' + c.btnColor;
        noteInput.className = noteInput.className.replace(/focus:ring-\S+/g, '') + ' ' + c.ringColor;

        icon.innerHTML  = c.icon;
        title.textContent = c.title;

        document.getElementById('confirmForm').action = formAction;
        noteInput.value = '';

        elModal.classList.remove('hidden');
        setTimeout(() => { elModal.classList.remove('opacity-0'); elCard.classList.remove('scale-95'); }, 10);
    }

    function closeConfirmModal() {
        elModal.classList.add('opacity-0');
        elCard.classList.add('scale-95');
        setTimeout(() => elModal.classList.add('hidden'), 300);
    }

    elModal.addEventListener('click', e => { if (e.target === elModal) closeConfirmModal(); });
</script>
@endpush
