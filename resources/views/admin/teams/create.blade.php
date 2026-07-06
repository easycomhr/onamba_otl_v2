@extends('layouts.admin')

@section('title', __('ui.team.create_title'))
@section('page_title', __('ui.team.create_title'))

@section('content')
<nav class="mb-6">
    <a href="{{ route('admin.teams.index') }}" class="flex items-center text-gray-500 hover:text-blue-600 transition-colors w-fit">
        <svg class="w-6 h-6 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
        <span class="font-semibold">{{ __('ui.common.back_to_list') }}</span>
    </a>
</nav>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 px-5 py-4 border-b border-gray-200">
            <h2 class="text-[17px] font-bold text-gray-800 uppercase tracking-wide">{{ __('ui.nav.teams') }}</h2>
        </div>
        <form method="POST" action="{{ route('admin.teams.store') }}" class="p-5 space-y-5">
            @csrf

            <div>
                <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.team.name') }} <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-0 focus:border-blue-500 transition-colors">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>


<div>
                <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.team.description') }}</label>
                <textarea name="description" rows="3"
                          class="block w-full px-4 py-3 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-0 focus:border-blue-500 transition-colors">{{ old('description') }}</textarea>
                @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.team.main_approver') }}</label>
                <input type="hidden" name="main_approver_id" id="main_approver_id" value="{{ old('main_approver_id') }}">
                <div class="relative" id="approver-dropdown">
                    <button type="button" id="approver-trigger"
                            class="flex items-center justify-between w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors bg-white text-left">
                        <span id="approver-display" class="truncate">— {{ __('ui.team.none') }} —</span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                    <div id="approver-panel" class="absolute z-20 w-full bg-white border-2 border-blue-500 rounded-xl shadow-lg mt-1 hidden">
                        <div class="p-2 border-b border-gray-100">
                            <input type="text" id="approver-search" placeholder="{{ __('ui.employee.search_placeholder') }}"
                                   class="block w-full px-3 py-2 text-[14px] text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                        <ul id="approver-list" class="max-h-52 overflow-y-auto py-1">
                            <li data-value="" data-label="— {{ __('ui.team.none') }} —"
                                class="px-4 py-2 text-[14px] text-gray-500 cursor-pointer hover:bg-blue-50 approver-option">— {{ __('ui.team.none') }} —</li>
                            @foreach($managers as $manager)
                            <li data-value="{{ $manager->id }}" data-label="{{ $manager->name }} ({{ $manager->employee_code }})"
                                class="px-4 py-2 text-[14px] text-gray-800 cursor-pointer hover:bg-blue-50 approver-option">
                                {{ $manager->name }} <span class="text-gray-400">({{ $manager->employee_code }})</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @error('main_approver_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            @php
                $selectedSubApprovers = old('sub_approver_ids', []);
            @endphp
            <div>
                <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.team.sub_approvers') }}</label>

                {{-- Hidden inputs to submit selected sub-approver IDs --}}
                <div id="sub-approver-hidden-inputs">
                    @foreach((array) $selectedSubApprovers as $sid)
                        <input type="hidden" name="sub_approver_ids[]" value="{{ $sid }}" class="sub-approver-hidden">
                    @endforeach
                </div>

                {{-- Custom searchable dropdown --}}
                <div class="relative" id="sub-approver-dropdown">
                    <button type="button" id="sub-approver-trigger"
                            class="flex items-center justify-between w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors bg-white text-left">
                        <span id="sub-approver-display" class="truncate text-gray-500">— {{ __('ui.team.none') }} —</span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>

                    <div id="sub-approver-panel" class="absolute z-20 w-full bg-white border-2 border-blue-500 rounded-xl shadow-lg mt-1 hidden">
                        {{-- Search --}}
                        <div class="p-2 border-b border-gray-100">
                            <input type="text" id="sub-approver-search" placeholder="{{ __('ui.employee.search_placeholder') }}"
                                   class="block w-full px-3 py-2 text-[14px] text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        </div>

                        {{-- Checkbox list --}}
                        <ul id="sub-approver-list" class="max-h-52 overflow-y-auto py-1">
                            @foreach($managers as $manager)
                            <li class="sub-approver-option px-3 py-2 flex items-center gap-3 hover:bg-blue-50 cursor-pointer rounded-lg mx-1"
                                data-search="{{ strtolower($manager->name . ' ' . $manager->employee_code) }}"
                                data-value="{{ $manager->id }}"
                                data-label="{{ $manager->name }} ({{ $manager->employee_code }})">
                                <input type="checkbox" class="sub-approver-check w-4 h-4 rounded text-blue-600 border-gray-300 flex-shrink-0 pointer-events-none"
                                       {{ in_array($manager->id, (array) $selectedSubApprovers) ? 'checked' : '' }}>
                                <span class="text-[14px] text-gray-800 truncate">
                                    {{ $manager->name }}
                                    <span class="text-gray-400 font-mono text-[12px]">({{ $manager->employee_code }})</span>
                                </span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <p class="text-xs text-gray-500 mt-1">{{ __('ui.common.note_optional') }}</p>
                @error('sub_approver_ids')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                @error('sub_approver_ids.*')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>



            <div>
                <label class="block text-[15px] font-bold text-gray-800 mb-2">MS Teams Webhook URL</label>
                <input type="url" name="ms_teams_webhook_url" value="{{ old('ms_teams_webhook_url') }}" placeholder="https://..."
                       class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-0 focus:border-blue-500 transition-colors">
                @error('ms_teams_webhook_url')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3 pt-2">
                <a href="{{ route('admin.teams.index') }}"
                   class="flex-1 flex items-center justify-center min-h-[50px] bg-white border-2 border-gray-300 text-gray-600 font-bold uppercase rounded-xl hover:bg-gray-50 transition-colors">
                    {{ __('ui.common.cancel') }}
                </a>
                <button type="submit"
                        class="flex-1 min-h-[50px] bg-blue-600 text-white font-bold uppercase rounded-xl shadow-md hover:bg-blue-700 transition-colors">
                    {{ __('ui.team.create_title') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const initialValue = document.getElementById('main_approver_id').value;
    const trigger = document.getElementById('approver-trigger');
    const display = document.getElementById('approver-display');
    const panel = document.getElementById('approver-panel');
    const search = document.getElementById('approver-search');
    const hiddenInput = document.getElementById('main_approver_id');
    const options = document.querySelectorAll('.approver-option');

    // Set initial display from old() value if present
    if (initialValue) {
        options.forEach(opt => {
            if (opt.dataset.value === initialValue) {
                display.textContent = opt.dataset.label;
                opt.classList.add('bg-blue-50', 'font-semibold');
            }
        });
    }

    trigger.addEventListener('click', () => {
        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden')) {
            search.value = '';
            options.forEach(o => o.style.display = '');
            search.focus();
        }
    });

    search.addEventListener('input', () => {
        const q = search.value.toLowerCase();
        options.forEach(opt => {
            opt.style.display = opt.dataset.label.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    options.forEach(opt => {
        opt.addEventListener('click', () => {
            hiddenInput.value = opt.dataset.value;
            display.textContent = opt.dataset.label;
            options.forEach(o => o.classList.remove('bg-blue-50', 'font-semibold'));
            opt.classList.add('bg-blue-50', 'font-semibold');
            panel.classList.add('hidden');
        });
    });

    document.addEventListener('click', e => {
        if (!document.getElementById('approver-dropdown').contains(e.target)) {
            panel.classList.add('hidden');
        }
    });
})();

// Sub Approvers searchable dropdown
(function () {
    const dropdown  = document.getElementById('sub-approver-dropdown');
    const trigger   = document.getElementById('sub-approver-trigger');
    const display   = document.getElementById('sub-approver-display');
    const panel     = document.getElementById('sub-approver-panel');
    const searchEl  = document.getElementById('sub-approver-search');
    const options   = document.querySelectorAll('.sub-approver-option');
    const hiddenContainer = document.getElementById('sub-approver-hidden-inputs');

    function getSelectedIds() {
        return Array.from(options)
            .filter(o => o.querySelector('.sub-approver-check').checked)
            .map(o => o.dataset.value);
    }

    function syncHiddenInputs() {
        hiddenContainer.innerHTML = '';
        getSelectedIds().forEach(id => {
            const inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = 'sub_approver_ids[]';
            inp.value = id;
            hiddenContainer.appendChild(inp);
        });
    }

    function updateDisplay() {
        const ids = getSelectedIds();
        if (ids.length === 0) {
            display.textContent = '— {{ __("ui.team.none") }} —';
            display.classList.add('text-gray-500');
            display.classList.remove('text-gray-800');
        } else {
            const labels = Array.from(options)
                .filter(o => o.querySelector('.sub-approver-check').checked)
                .map(o => o.dataset.label);
            display.textContent = ids.length <= 2
                ? labels.join(', ')
                : labels.slice(0, 2).join(', ') + ' +' + (ids.length - 2);
            display.classList.remove('text-gray-500');
            display.classList.add('text-gray-800');
        }
    }

    // Initialise from checked state (set by Blade)
    updateDisplay();

    trigger.addEventListener('click', () => {
        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden')) {
            searchEl.value = '';
            options.forEach(o => o.style.display = '');
            searchEl.focus();
        }
    });

    searchEl.addEventListener('input', () => {
        const q = searchEl.value.toLowerCase();
        options.forEach(o => {
            o.style.display = o.dataset.search.includes(q) ? '' : 'none';
        });
    });

    options.forEach(opt => {
        opt.addEventListener('click', () => {
            const chk = opt.querySelector('.sub-approver-check');
            chk.checked = !chk.checked;
            opt.classList.toggle('bg-blue-50', chk.checked);
            syncHiddenInputs();
            updateDisplay();
        });
    });

    // Highlight already-checked items on load
    options.forEach(opt => {
        if (opt.querySelector('.sub-approver-check').checked) {
            opt.classList.add('bg-blue-50');
        }
    });

    document.addEventListener('click', e => {
        if (!dropdown.contains(e.target)) {
            panel.classList.add('hidden');
        }
    });
})();
</script>
@endpush
