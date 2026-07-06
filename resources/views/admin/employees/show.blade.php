@extends('layouts.admin')

@section('title', __('ui.employee.detail_title'))
@section('page_title', __('ui.employee.detail_title'))

@section('content')
<nav class="mb-6">
    <a href="{{ route('admin.employees.index') }}" class="flex items-center text-gray-500 hover:text-blue-600 transition-colors w-fit">
        <svg class="w-6 h-6 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
        <span class="font-semibold">{{ __('ui.common.back_to_list') }}</span>
    </a>
</nav>

<div class="max-w-2xl mx-auto space-y-6">

    {{-- Thông tin nhân viên --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 px-5 py-4 border-b border-gray-200">
            <h2 class="text-[17px] font-bold text-gray-800 uppercase tracking-wide">{{ __('ui.employee.detail_title') }}</h2>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-[13px] font-bold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('ui.employee.code') }}</label>
                    <div class="bg-gray-100 min-h-[50px] px-4 py-3 flex items-center rounded-xl font-bold text-gray-700 border border-gray-200 text-[15px]">
                        {{ $employee->employee_code }}
                    </div>
                </div>
                <div>
                    <label class="block text-[13px] font-bold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('ui.employee.full_name') }}</label>
                    <div class="bg-gray-100 min-h-[50px] px-4 py-3 flex items-center rounded-xl text-gray-800 border border-gray-200 text-[15px]">
                        {{ $employee->name }}
                    </div>
                </div>
                <div>
                    <label class="block text-[13px] font-bold text-gray-500 uppercase tracking-wide mb-1.5">Email</label>
                    <div class="bg-gray-100 min-h-[50px] px-4 py-3 flex items-center rounded-xl text-gray-800 border border-gray-200 text-[15px]">
                        {{ $employee->email ?? '—' }}
                    </div>
                </div>
                <div>
                    <label class="block text-[13px] font-bold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('ui.employee.department') }}</label>
                    <div class="bg-gray-100 min-h-[50px] px-4 py-3 flex items-center rounded-xl text-gray-800 border border-gray-200 text-[15px]">
                        {{ $employee->department }}
                    </div>
                </div>
                <div>
                    <label class="block text-[13px] font-bold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('ui.employee.position') }}</label>
                    <div class="bg-gray-100 min-h-[50px] px-4 py-3 flex items-center rounded-xl text-gray-800 border border-gray-200 text-[15px]">
                        {{ $employee->position }}
                    </div>
                </div>
                <div>
                    <label class="block text-[13px] font-bold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('ui.leave.balance') }}</label>
                    <div class="bg-gray-100 min-h-[50px] px-4 py-3 flex items-center rounded-xl border border-gray-200">
                        <span class="inline-flex items-center px-3 py-1 bg-green-50 text-green-700 font-bold rounded-lg text-sm">
                            {{ $employee->annual_leave_balance }} {{ __('ui.common.unit_day') }}
                        </span>
                    </div>
                </div>
                <div>
                    <label class="block text-[13px] font-bold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('ui.employee.team') }}</label>
                    <div class="bg-gray-100 min-h-[50px] px-4 py-3 flex items-center rounded-xl text-gray-800 border border-gray-200 text-[15px]">
                        {{ $employee->team?->name ?? '—' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Thống kê --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 px-5 py-4 border-b border-gray-200">
            <h2 class="text-[17px] font-bold text-gray-800 uppercase tracking-wide">Thống kê</h2>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="bg-blue-50 rounded-xl p-4 text-center">
                    <p class="text-[13px] font-bold text-blue-600 uppercase tracking-wide mb-1">{{ __('ui.employee.ot_count') }}</p>
                    <p class="text-3xl font-extrabold text-blue-700">{{ $otCount }}</p>
                    <p class="text-sm text-blue-500 mt-0.5">đơn</p>
                </div>
                <div class="bg-green-50 rounded-xl p-4 text-center">
                    <p class="text-[13px] font-bold text-green-600 uppercase tracking-wide mb-1">{{ __('ui.employee.leave_count') }}</p>
                    <p class="text-3xl font-extrabold text-green-700">{{ $leaveCount }}</p>
                    <p class="text-sm text-green-500 mt-0.5">đơn</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <a href="{{ route('admin.employees.index') }}"
           class="w-full sm:flex-1 flex items-center justify-center min-h-[50px] bg-white border-2 border-gray-300 text-gray-600 font-bold uppercase rounded-xl hover:bg-gray-50 transition-colors">
            {{ __('ui.common.back_to_list') }}
        </a>
        <button type="button"
                onclick="confirmDeleteEmployee()"
                class="w-full sm:flex-1 flex items-center justify-center min-h-[50px] bg-red-600 text-white font-bold uppercase rounded-xl shadow-md hover:bg-red-700 transition-colors">
            {{ __('ui.common.delete') }}
        </button>
        <a href="{{ route('admin.employees.edit', $employee->id) }}"
           class="w-full sm:flex-1 flex items-center justify-center min-h-[50px] bg-blue-600 text-white font-bold uppercase rounded-xl shadow-md hover:bg-blue-700 transition-colors">
            {{ __('ui.common.edit') }}
        </a>
    </div>

</div>
@endsection

@push('cdn_scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@push('scripts')
<script>
async function confirmDeleteEmployee() {
    const result = await Swal.fire({
        title: '{{ __("ui.employee.delete") }}',
        text: {{ json_encode(__('ui.employee.delete_confirm', ['name' => $employee->name])) }},
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '{{ __("ui.common.delete") }}',
        cancelButtonText: '{{ __("ui.common.cancel") }}',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
    });
    if (result.isConfirmed) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.employees.destroy", $employee->id) }}';
        [['_token', '{{ csrf_token() }}'], ['_method', 'DELETE']].forEach(([n, v]) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = n; inp.value = v;
            form.appendChild(inp);
        });
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
