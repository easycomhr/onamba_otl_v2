@extends('layouts.admin')

@section('title', __('ui.nav.leave_register'))
@section('page_title', __('ui.nav.leave_register'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <h1 class="text-2xl font-extrabold text-gray-900 uppercase tracking-tight hidden md:block">{{ __('ui.nav.leave_register') }}</h1>

    @if($errors->has('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-200">
        <form method="POST" action="{{ route('admin.register.leave.store') }}" class="space-y-5">
            @csrf

            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.table.employee') }} <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    id="employee_search"
                    placeholder="{{ __('ui.employee.search_placeholder') }}"
                    autocomplete="off"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <input type="hidden" name="employee_id" id="employee_id_input" value="{{ old('employee_id') }}">
                <div id="employee_dropdown" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                @error('employee_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="leave_type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.leave.type') }} <span class="text-red-500">*</span></label>
                <select
                    name="leave_type"
                    id="leave_type"
                    required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">-- {{ __('ui.leave.type') }} --</option>
                    @foreach($leaveTypes as $key => $label)
                        <option value="{{ $key }}" @selected(old('leave_type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('leave_type')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="from_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.leave.from_date') }} <span class="text-red-500">*</span></label>
                    <input
                        type="date"
                        name="from_date"
                        id="from_date"
                        required
                        value="{{ old('from_date') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    @error('from_date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="to_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.leave.to_date') }} <span class="text-red-500">*</span></label>
                    <input
                        type="date"
                        name="to_date"
                        id="to_date"
                        required
                        value="{{ old('to_date') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    @error('to_date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.leave.reason') }} <span class="text-red-500">*</span></label>
                <textarea
                    name="reason"
                    id="reason"
                    rows="3"
                    required
                    placeholder="{{ __('ui.leave.reason') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                >{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <a href="{{ route('admin.approvals.leave.index') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    {{ __('ui.common.cancel') }}
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    {{ __('ui.nav.leave_register') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('employee_search');
    const hiddenInput = document.getElementById('employee_id_input');
    const dropdown = document.getElementById('employee_dropdown');
    let debounceTimer = null;

    const employeeSearchUrl = @json(route('admin.register.leave.employees'));

    const hideDropdown = () => {
        dropdown.classList.add('hidden');
    };

    const showDropdown = () => {
        dropdown.classList.remove('hidden');
    };

    const getEmployees = (json) => {
        if (Array.isArray(json.data)) {
            return json.data;
        }

        if (Array.isArray(json)) {
            return json;
        }

        return [];
    };

    const renderEmployees = (employees) => {
        if (employees.length === 0) {
            dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-gray-400">{{ __('ui.employee.not_found') }}</div>';
            return;
        }

        dropdown.innerHTML = employees.map(e => `
            <div class="px-3 py-2 text-sm hover:bg-blue-50 cursor-pointer employee-option"
                 data-id="${e.id}" data-label="${e.employee_code} — ${e.name}">
                <span class="font-medium">${e.employee_code}</span> — ${e.name}
                ${e.department ? '<span class="text-gray-400 text-xs ml-1">(' + e.department + ')</span>' : ''}
            </div>
        `).join('');

        dropdown.querySelectorAll('.employee-option').forEach(item => {
            item.addEventListener('click', function () {
                hiddenInput.value = this.dataset.id;
                searchInput.value = this.dataset.label;
                hideDropdown();
            });
        });
    };

    const loadEmployees = (query) => {
        fetch(employeeSearchUrl + '?search=' + encodeURIComponent(query))
            .then(r => r.json())
            .then(json => {
                renderEmployees(getEmployees(json));
            })
            .catch(() => {
                dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-red-500">{{ __('ui.flash.error_generic') }}</div>';
            });
    };

    const oldId = hiddenInput.value;
    if (oldId) {
        fetch(employeeSearchUrl + '?search=')
            .then(r => r.json())
            .then(json => {
                const employees = getEmployees(json);
                const found = employees.find(e => String(e.id) === String(oldId));

                if (found) {
                    searchInput.value = found.employee_code + ' — ' + found.name;
                }
            })
            .catch(() => {});
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        hiddenInput.value = '';

        const q = this.value.trim();
        if (q.length < 1) {
            hideDropdown();
            return;
        }

        dropdown.innerHTML = '<div class="px-3 py-2 text-sm text-gray-400">{{ __('ui.common.search') }}...</div>';
        showDropdown();

        debounceTimer = setTimeout(() => {
            loadEmployees(q);
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            hideDropdown();
        }
    });
});
</script>
@endpush
