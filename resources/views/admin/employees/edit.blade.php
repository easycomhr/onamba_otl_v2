@extends('layouts.admin')

@section('title', __('ui.common.edit') . ' ' . __('ui.nav.employees'))
@section('page_title', __('ui.common.edit') . ' ' . __('ui.nav.employees'))

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
            <h2 class="text-[17px] font-bold text-gray-800 uppercase tracking-wide">{{ __('ui.nav.employees') }}</h2>
        </div>
        <form method="POST" action="{{ route('admin.employees.update', $employee->id) }}" class="p-5 space-y-5">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.employee.code') }}</label>
                    <div class="bg-gray-100 min-h-[50px] px-4 py-3 flex items-center rounded-xl font-bold text-gray-600 border border-gray-200 text-[15px]">{{ $employee->employee_code }}</div>
                </div>
                <div>
                    <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.employee.full_name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $employee->name) }}" required
                           class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-0 focus:border-blue-500 transition-colors">
                    @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[15px] font-bold text-gray-800 mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email', $employee->email) }}"
                           class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-0 focus:border-blue-500 transition-colors">
                    @error('email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.employee.department') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="department" value="{{ old('department', $employee->department) }}" required
                           class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-0 focus:border-blue-500 transition-colors">
                    @error('department')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.employee.position') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="position" value="{{ old('position', $employee->position) }}" required
                           class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-0 focus:border-blue-500 transition-colors">
                    @error('position')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.leave.balance') }}</label>
                    <div class="relative">
                        <input type="number" name="annual_leave_balance" value="{{ old('annual_leave_balance', $employee->annual_leave_balance) }}" min="0"
                               class="block w-full min-h-[50px] pl-4 pr-14 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-0 focus:border-blue-500 transition-colors">
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <span class="text-gray-500 font-medium">{{ __('ui.common.unit_day') }}</span>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.employee.team') }}</label>
                    <select name="team_id"
                            class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-0 focus:border-blue-500 transition-colors bg-white">
                        <option value="">— {{ __('ui.team.not_in_team') }} —</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ old('team_id', $employee->team_id) == $team->id ? 'selected' : '' }}>
                                {{ $team->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('team_id')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.admin_role.grant_label') }}</label>
                    <select name="admin_role_id"
                            class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-0 focus:border-indigo-500 transition-colors bg-white">
                        <option value="">— {{ __('ui.admin_role.no_admin_access') }} —</option>
                        @foreach($adminRoles as $ar)
                            <option value="{{ $ar->id }}" {{ old('admin_role_id', $employee->admin_role_id) == $ar->id ? 'selected' : '' }}>
                                {{ $ar->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">{{ __('ui.admin_role.grant_hint') }}</p>
                    @error('admin_role_id')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="button"
                        onclick="confirmDeleteEmployee()"
                        class="w-full sm:flex-1 flex items-center justify-center min-h-[50px] bg-red-50 text-red-700 border-2 border-red-200 font-bold uppercase rounded-xl hover:bg-red-100 transition-colors">
                    {{ __('ui.common.delete') }}
                </button>
                <a href="{{ route('admin.employees.index') }}"
                   class="w-full sm:flex-1 flex items-center justify-center min-h-[50px] bg-white border-2 border-gray-300 text-gray-600 font-bold uppercase rounded-xl hover:bg-gray-50 transition-colors">
                    {{ __('ui.common.cancel') }}
                </a>
                <button type="submit"
                        class="w-full sm:flex-1 min-h-[50px] bg-blue-600 text-white font-bold uppercase rounded-xl shadow-md hover:bg-blue-700 transition-colors">
                    {{ __('ui.common.save') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Đổi mật khẩu --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 px-5 py-4 border-b border-gray-200">
            <h2 class="text-[17px] font-bold text-gray-800 uppercase tracking-wide">{{ __('ui.profile.change_password') }}</h2>
        </div>
        <form method="POST" action="{{ route('admin.employees.change-password', $employee->id) }}" class="p-5 space-y-5">
            @csrf
            <div>
                <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.profile.new_password') }} <span class="text-red-500">*</span></label>
                <input type="password" name="password" required minlength="8"
                       class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-0 focus:border-blue-500 transition-colors">
            </div>
            <div>
                <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.profile.confirm_password') }} <span class="text-red-500">*</span></label>
                <input type="password" name="password_confirmation" required minlength="8"
                       class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-0 focus:border-blue-500 transition-colors">
            </div>
            <button type="submit"
                    class="w-full min-h-[50px] bg-gray-800 text-white font-bold uppercase rounded-xl shadow-md hover:bg-gray-900 transition-colors">
                {{ __('ui.profile.change_password') }}
            </button>
        </form>
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
