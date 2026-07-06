@extends('layouts.admin')

@section('title', __('ui.nav.employees'))
@section('page_title', __('ui.nav.employees'))

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-extrabold text-gray-900 uppercase tracking-tight hidden md:block">{{ __('ui.nav.employees') }}</h1>
    <a href="{{ route('admin.employees.create') }}"
       class="flex items-center px-4 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors text-sm">
        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        {{ __('ui.employee.add_new') }}
    </a>
</div>

{{-- Search bar --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('admin.employees.index') }}" class="flex gap-3">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input type="text" name="q" placeholder="{{ __('ui.employee.search_placeholder') }}" value="{{ request('q') }}"
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-[15px] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
        </div>
        <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors text-[15px]">{{ __('ui.common.search') }}</button>
    </form>
</div>

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b border-gray-200">
        <tr>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm">{{ __('ui.employee.code_short') }}</th>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm">{{ __('ui.employee.full_name') }}</th>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm hidden md:table-cell">{{ __('ui.employee.department') }}</th>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm hidden md:table-cell">{{ __('ui.employee.position') }}</th>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm text-center hidden md:table-cell">{{ __('ui.leave.balance') }}</th>
            <th class="px-6 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm text-center">{{ __('ui.common.action') }}</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
        @forelse($employees as $emp)
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 font-bold text-blue-600">{{ $emp->employee_code }}</td>
            <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-600 text-sm flex-shrink-0">
                        {{ mb_substr($emp->name, 0, 1) }}
                    </div>
                    <span class="font-semibold text-gray-800">{{ $emp->name }}</span>
                </div>
            </td>
            <td class="px-6 py-4 text-gray-600 hidden md:table-cell">{{ $emp->department }}</td>
            <td class="px-6 py-4 text-gray-600 hidden md:table-cell">{{ $emp->position }}</td>
            <td class="px-6 py-4 text-center hidden md:table-cell">
                <span class="inline-flex items-center px-3 py-1 bg-green-50 text-green-700 font-bold rounded-lg text-sm">{{ $emp->annual_leave_balance }} {{ __('ui.common.unit_day') }}</span>
            </td>
            <td class="px-6 py-4 text-center">
                <div class="flex items-center justify-center gap-2">
                    <a href="{{ route('admin.employees.show', $emp->id) }}"
                       class="flex items-center px-3 py-1.5 bg-gray-50 text-gray-700 hover:bg-gray-100 rounded-lg text-sm font-semibold transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.58-3.007-9.964-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ __('ui.employee.detail') }}
                    </a>
                    <button type="button"
                            onclick='confirmDeleteEmployee({{ $emp->id }}, @json($emp->name))'
                            class="flex items-center px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 rounded-lg text-sm font-semibold transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                        </svg>
                        {{ __('ui.common.delete') }}
                    </button>
                    <a href="{{ route('admin.employees.edit', $emp->id) }}"
                       class="flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg text-sm font-semibold transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                        </svg>
                        {{ __('ui.common.edit') }}
                    </a>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="px-6 py-10 text-center text-gray-400">{{ __('ui.employee.not_found') }}</td>
        </tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

{{-- Pagination --}}
@if($employees->hasPages())
<div class="mt-4">{{ $employees->links() }}</div>
@endif

@endsection

@push('cdn_scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@push('scripts')
<script>
async function confirmDeleteEmployee(id, name) {
    const result = await Swal.fire({
        title: '{{ __("ui.employee.delete") }}',
        text: '{{ __("ui.employee.delete_confirm") }}'.replace(':name', name),
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
        form.action = `/admin/employees/${id}`;
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
