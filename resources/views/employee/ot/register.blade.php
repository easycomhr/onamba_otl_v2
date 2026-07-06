@extends('layouts.employee')

@section('title', __('ui.ot.register_page_title') . ' - OTMS')

@section('content')

<div class="bg-white shadow-sm flex items-center p-4 relative z-10">
    <a href="{{ request('ref') === 'list' ? route('employee.ot.index') : route('employee.dashboard') }}" class="absolute left-4 flex items-center text-gray-600 hover:text-blue-600 active:scale-95 transition-all p-2 -ml-2 rounded-lg">
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
    </a>
    <h1 class="text-xl font-bold text-blue-600 uppercase w-full text-center">{{ __('ui.ot.register_page_title') }}</h1>
</div>

<div class="flex-1 overflow-y-auto p-4 sm:p-5">

    @if($errors->has('error'))
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 text-sm text-red-700">
            {{ $errors->first('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-4 mb-6 flex items-start shadow-sm">
        <svg class="w-6 h-6 text-yellow-600 flex-shrink-0 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <p class="text-[15px] text-yellow-800 leading-snug">
            <span class="font-bold">{{ __('ui.common.note') }}:</span> {{ __('ui.ot.register_page_title') }}
        </p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-5 sm:p-6 mb-6">
        <form id="otForm" method="POST" action="{{ route('employee.ot.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="ot_date" class="block text-[17px] font-bold text-gray-800 mb-2">{{ __('ui.ot.register_date') }}</label>
                <div class="relative">
                    <input type="date" id="ot_date" name="ot_date" value="{{ old('ot_date') }}" required
                           class="appearance-none block w-full min-h-[50px] px-4 py-[13px] pr-10 text-[17px] text-gray-800 bg-white border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors @error('ot_date') border-red-500 @enderror">
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4">
                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                        </svg>
                    </div>
                </div>
                @error('ot_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="hours" class="block text-[17px] font-bold text-gray-800 mb-2">{{ __('ui.ot.hours') }}</label>
                <div class="relative">
                    <input type="number" id="hours" name="hours" step="0.5" min="0.5"
                           value="{{ old('hours') }}" placeholder="2" required
                           class="block w-full min-h-[50px] pl-4 pr-12 text-[17px] text-gray-800 bg-white border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors placeholder-gray-500 @error('hours') border-red-500 @enderror">
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                        <span class="text-gray-500 font-medium">{{ __('ui.common.unit_hour') }}</span>
                    </div>
                </div>
                @error('hours')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="reason" class="block text-[17px] font-bold text-gray-800 mb-2">{{ __('ui.ot.reason') }}</label>
                <textarea id="reason" name="reason" rows="3" required
                          placeholder="{{ __('ui.ot.reason') }}"
                          class="block w-full min-h-[50px] p-4 text-[17px] text-gray-800 bg-white border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors placeholder-gray-500 resize-none @error('reason') border-red-500 @enderror">{{ old('reason') }}</textarea>
                @error('reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

        </form>
    </div>

    <div class="space-y-4 pb-6">
        <button type="button" onclick="openModal()"
                class="w-full min-h-[54px] bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-lg uppercase rounded-xl shadow-md transition-all duration-200 active:scale-[0.98]">
            {{ __('ui.ot.register_page_title') }}
        </button>
        <a href="{{ request('ref') === 'list' ? route('employee.ot.index') : route('employee.dashboard') }}"
           class="flex items-center justify-center w-full min-h-[54px] bg-white border-2 border-gray-300 hover:bg-gray-50 text-gray-600 font-bold text-lg uppercase rounded-xl transition-all duration-200 active:scale-[0.98]">
            {{ __('ui.common.cancel') }}
        </a>
    </div>
</div>

@endsection

@push('modals')
<div id="confirmModal" class="fixed inset-0 bg-black/50 z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4">
    <div id="modalCard" class="bg-white w-full max-w-sm rounded-3xl shadow-2xl p-6 transform scale-95 transition-transform duration-300">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-50 mb-5">
            <svg class="h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
            </svg>
        </div>
        <div class="text-center mb-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-2">{{ __('ui.common.confirm') }}</h3>
            <p class="text-[16px] text-gray-600">{{ __('ui.ot.confirm_submit') }}</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <button type="button" onclick="closeModal()"
                    class="w-full min-h-[50px] bg-white border-2 border-gray-300 text-gray-600 font-bold text-[16px] uppercase rounded-xl active:bg-gray-100 transition-colors">
                {{ __('ui.common.back') }}
            </button>
            <button type="button" onclick="confirmSubmit()"
                    class="w-full min-h-[50px] bg-blue-600 text-white font-bold text-[16px] uppercase rounded-xl shadow-md active:bg-blue-700 transition-colors">
                {{ __('ui.common.confirm') }}
            </button>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<style>
    input[type="date"]::-webkit-date-and-time-value { text-align: left; display: flex; align-items: center; height: 100%; }
    input[type="date"]::-webkit-inner-spin-button,
    input[type="date"]::-webkit-calendar-picker-indicator { opacity: 0; position: absolute; right: 0; width: 100%; height: 100%; cursor: pointer; }
</style>
<script>
    const modal = document.getElementById('confirmModal');
    const modalCard = document.getElementById('modalCard');
    function openModal() {
        modal.classList.remove('hidden');
        setTimeout(() => { modal.classList.remove('opacity-0'); modalCard.classList.remove('scale-95'); modalCard.classList.add('scale-100'); }, 10);
    }
    function closeModal() {
        modal.classList.add('opacity-0'); modalCard.classList.remove('scale-100'); modalCard.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }
    function confirmSubmit() { document.getElementById('otForm').submit(); }
    modal.addEventListener('click', e => { if(e.target === modal) closeModal(); });
</script>
@endpush
