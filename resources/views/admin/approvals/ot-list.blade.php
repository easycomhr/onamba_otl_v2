@extends('layouts.admin')

@section('title', __('ui.approval.ot_list_title'))
@section('page_title', __('ui.nav.ot_approvals'))

@section('content')
<nav class="mb-6">
    <a href="{{ route('admin.dashboard') }}" class="flex items-center text-gray-500 hover:text-blue-600 transition-colors w-fit">
        <svg class="w-6 h-6 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
        <span class="font-semibold">{{ __('ui.common.back_to_dashboard') }}</span>
    </a>
</nav>

<h1 class="text-xl font-bold text-gray-800 uppercase mb-6 hidden md:block">{{ __('ui.approval.ot_list_title') }}</h1>

<div class="mb-6 flex items-center text-gray-600">
    <svg class="w-5 h-5 mr-2 text-blue-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
    </svg>
    <span><strong class="text-blue-600">{{ $otRequests->where('status', 'pending')->count() }}</strong> {{ __('ui.common.unit_request') }}</span>
</div>

{{-- Filter bar --}}
<form method="GET" action="{{ route('admin.approvals.ot.index') }}" class="mb-5 flex flex-wrap gap-3">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('ui.employee.search_placeholder') }}"
           class="flex-1 min-w-[200px] px-4 py-2.5 border border-gray-300 rounded-xl text-[15px] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    <select name="status" class="px-4 py-2.5 border border-gray-300 rounded-xl text-[15px] focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">{{ __('ui.status.all') }}</option>
        <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>{{ __('ui.status.pending') }}</option>
        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('ui.status.approved') }}</option>
        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('ui.status.rejected') }}</option>
    </select>
    <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors text-[15px]">{{ __('ui.common.filter') }}</button>
</form>

<div class="md:bg-white md:rounded-2xl md:shadow-sm md:border md:border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead class="hidden md:table-header-group bg-gray-50 border-b border-gray-200">
        <tr>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm">{{ __('ui.ot.request_code') }}</th>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm">{{ __('ui.table.employee') }}</th>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm">{{ __('ui.ot.ot_date') }}</th>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm text-center">{{ __('ui.ot.hours_short') }}</th>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm">{{ __('ui.ot.content') }}</th>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm text-center">{{ __('ui.common.action') }}</th>
        </tr>
        </thead>

        <tbody class="block md:table-row-group">
        @forelse($otRequests as $req)
        <tr class="block md:table-row bg-white rounded-2xl shadow-sm mb-5 md:mb-0 md:rounded-none md:shadow-none md:border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="flex justify-between md:table-cell px-5 py-3 md:px-6 md:py-4 border-b border-gray-50 md:border-none">
                <span class="md:hidden font-medium text-gray-500">{{ __('ui.ot.request_code') }}:</span>
                <span class="font-bold text-gray-800 md:text-blue-600">#{{ $req->code }}</span>
            </td>
            <td class="flex justify-between items-center md:table-cell px-5 py-3 md:px-6 md:py-4 border-b border-gray-50 md:border-none">
                <span class="md:hidden font-medium text-gray-500">{{ __('ui.table.employee') }}:</span>
                <div class="text-right md:text-left">
                    <p class="font-bold text-gray-800">{{ $req->employee->name }}</p>
                    <p class="text-sm text-gray-500">{{ $req->employee->employee_code }}</p>
                </div>
            </td>
            <td class="flex justify-between md:table-cell px-5 py-3 md:px-6 md:py-4 border-b border-gray-50 md:border-none">
                <span class="md:hidden font-medium text-gray-500">{{ __('ui.ot.ot_date') }}:</span>
                <span class="font-semibold text-gray-800">{{ $req->ot_date->format('d/m/Y') }}</span>
            </td>
            <td class="flex justify-between md:table-cell px-5 py-3 md:px-6 md:py-4 border-b border-gray-50 md:border-none md:text-center">
                <span class="md:hidden font-medium text-gray-500">{{ __('ui.ot.hours_short') }}:</span>
                <span class="inline-flex items-center justify-center px-3 py-1 bg-blue-50 text-blue-700 font-bold rounded-lg">
                    {{ $req->hours }} {{ __('ui.common.unit_hour') }}
                </span>
            </td>
            <td class="flex justify-between md:table-cell px-5 py-3 md:px-6 md:py-4 border-b border-gray-50 md:border-none">
                <span class="md:hidden font-medium text-gray-500 min-w-[80px]">{{ __('ui.ot.content') }}:</span>
                <span class="text-gray-600 truncate max-w-[180px] md:max-w-[200px] text-right md:text-left">{{ $req->reason }}</span>
            </td>
            <td class="block md:table-cell px-5 py-4 md:px-6 bg-gray-50/50 md:bg-transparent border-t md:border-none border-gray-100">
                @if($req->status === 'pending')
                    @if($req->isOverdue())
                    {{-- Overdue: ẩn Duyệt/Từ chối, chỉ hiện nút Cập nhật để vào detail xử lý SOS --}}
                    <a href="{{ route('admin.approvals.ot.show', $req->id) }}"
                       class="inline-flex items-center gap-1.5 px-2.5 py-2 rounded-xl font-bold text-sm transition-colors
                              {{ $req->hasSos() ? 'bg-orange-500 text-white hover:bg-orange-600' : 'bg-orange-50 text-orange-700 border border-orange-300 hover:bg-orange-100' }}">
                        <span>🆘</span>
                        <span>{{ __('ui.common.update') }}</span>
                    </a>
                    @else
                    {{-- Chưa overdue: hiện đủ 3 nút --}}
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('admin.approvals.ot.show', $req->id) }}"
                           class="flex items-center justify-center gap-2 min-h-[40px] px-3 py-2 border border-gray-300 text-gray-600 hover:bg-gray-100 hover:text-blue-600 rounded-xl font-semibold text-sm transition-colors">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>{{ __('ui.common.view_detail') }}</span>
                        </a>
                        <button type="button" onclick="openApproveModal('{{ $req->id }}', {{ $req->hours }})"
                                class="flex items-center justify-center gap-2 min-h-[40px] px-3 py-2 bg-green-50 text-green-700 border border-green-200 hover:bg-green-600 hover:text-white rounded-xl font-bold text-sm transition-colors">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            <span>{{ __('ui.common.approve') }}</span>
                        </button>
                        <button type="button" onclick="openRejectModal('{{ $req->id }}')"
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
                        'bg-green-100 text-green-700' => $req->status === 'approved',
                        'bg-red-100 text-red-700'     => $req->status === 'rejected',
                    ])>{{ $req->status_label }}</span>
                    <a href="{{ route('admin.approvals.ot.show', $req->id) }}"
                       class="inline-flex items-center gap-1.5 px-2.5 py-1.5 border border-gray-300 text-gray-600 hover:bg-gray-100 hover:text-blue-600 rounded-lg font-semibold text-xs transition-colors">
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
@if($otRequests->hasPages())
<div class="mt-4">{{ $otRequests->links() }}</div>
@endif

@endsection

@push('cdn_scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@push('scripts')
<script>
    const csrfToken = '{{ csrf_token() }}';

    async function openApproveModal(id, hours) {
        const { value: note, isConfirmed } = await Swal.fire({
            title: '{{ __("ui.approval.confirm_approve") }}',
            html: `
                <div class="text-left space-y-3 mt-1">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">{{ __("ui.common.note_optional") }}</label>
                        <textarea id="swal-note" rows="2" placeholder="{{ __("ui.common.note_optional") }}"
                                  class="block w-full px-4 py-2 border-2 border-gray-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '{{ __("ui.approval.confirm_approve") }}',
            cancelButtonText: '{{ __("ui.common.close") }}',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280',
            focusConfirm: false,
            preConfirm: () => {
                return document.getElementById('swal-note').value;
            }
        });

        if (isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/approvals/ot/${id}/approve`;
            [['_token', csrfToken], ['manager_note', note ?? '']].forEach(([name, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        }
    }

    async function openRejectModal(id) {
        const { value: note, isConfirmed } = await Swal.fire({
            title: '{{ __("ui.approval.confirm_reject") }}',
            input: 'textarea',
            inputLabel: '{{ __("ui.status.rejected_reason") }}',
            inputPlaceholder: '{{ __("ui.common.reject_reason_optional") }}',
            inputAttributes: { rows: 3 },
            showCancelButton: true,
            confirmButtonText: '{{ __("ui.approval.confirm_reject") }}',
            cancelButtonText: '{{ __("ui.common.close") }}',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return @json(__('ui.approval.enter_reject_reason_first'));
                }
            }
        });

        if (isConfirmed && note) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/approvals/ot/${id}/reject`;
            [['_token', csrfToken], ['manager_note', note.trim()]].forEach(([name, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endpush
