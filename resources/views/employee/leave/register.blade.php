@extends('layouts.employee')

@section('title', __('ui.leave.register_page_title') . ' - OTMS')

@section('content')

<div class="bg-white shadow-sm flex items-center p-4 relative z-10">
    <a href="{{ request('ref') === 'list' ? route('employee.leave.index') : route('employee.dashboard') }}" class="absolute left-4 flex items-center text-gray-600 hover:text-green-600 active:scale-95 transition-all p-2 -ml-2 rounded-lg">
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
    </a>
    <h1 class="text-xl font-bold text-green-600 uppercase w-full text-center">{{ __('ui.leave.register_page_title') }}</h1>
</div>

<div class="flex-1 overflow-y-auto p-4 sm:p-5">

    @if(session('success'))
    <div class="bg-green-50 border border-green-100 rounded-xl p-4 mb-6 flex items-start shadow-sm">
        <svg class="w-6 h-6 text-green-600 flex-shrink-0 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-[15px] text-green-800 leading-snug">{{ session('success') }}</p>
    </div>
    @endif

    @if($errors->has('error'))
    <div class="bg-red-50 border border-red-100 rounded-xl p-4 mb-6 flex items-start shadow-sm">
        <svg class="w-6 h-6 text-red-600 flex-shrink-0 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.401 3.003c1.155-2 4.043-2 5.198 0l5.598 9.694c1.155 2-.289 4.5-2.599 4.5H6.402c-2.31 0-3.754-2.5-2.599-4.5l5.598-9.694z"/>
        </svg>
        <p class="text-[15px] text-red-800 leading-snug">{{ $errors->first('error') }}</p>
    </div>
    @endif

    <div class="bg-green-50 border border-green-100 rounded-xl p-4 mb-6 flex items-start shadow-sm">
        <svg class="w-6 h-6 text-green-600 flex-shrink-0 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <p class="text-[15px] text-green-800 leading-snug">
            <span class="font-bold">{{ __('ui.leave.balance') }}:</span> <span class="text-green-700 font-bold text-lg">{{ $leaveBalance ?? 5 }} {{ __('ui.common.unit_day') }}</span>
        </p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-5 sm:p-6 mb-6">
        <form id="leaveForm" method="POST" action="{{ route('employee.leave.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="leave_type" class="block text-[17px] font-bold text-gray-800 mb-2">{{ __('ui.leave.type') }}</label>
                <div class="relative">
                    <select id="leave_type" name="leave_type" required
                            class="appearance-none block w-full min-h-[50px] px-4 pr-10 text-[17px] text-gray-800 bg-white border-2 border-gray-300 @error('leave_type') border-red-500 @enderror rounded-xl focus:outline-none focus:border-green-500 transition-colors">
                        <option value="annual" @selected(old('leave_type') === 'annual')>{{ __('ui.leave.type.annual') }}</option>
                        <option value="sick" @selected(old('leave_type') === 'sick')>{{ __('ui.leave.type.sick') }}</option>
                        <option value="personal" @selected(old('leave_type') === 'personal')>{{ __('ui.leave.type.personal') }}</option>
                        <option value="unpaid" @selected(old('leave_type') === 'unpaid')>{{ __('ui.leave.type.unpaid') }}</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4">
                        <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </div>
                </div>
                @error('leave_type')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label for="from_date" class="block text-[17px] font-bold text-gray-800 mb-2">{{ __('ui.leave.from_date') }}</label>
                    <div class="relative">
                        <input type="date" id="from_date" name="from_date" value="{{ old('from_date') }}" required
                               class="appearance-none block w-full min-h-[50px] px-4 py-[13px] pr-10 text-[16px] text-gray-800 bg-white border-2 border-gray-300 @error('from_date') border-red-500 @enderror rounded-xl focus:outline-none focus:border-green-500 transition-colors">
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4">
                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                            </svg>
                        </div>
                    </div>
                    @error('from_date')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="to_date" class="block text-[17px] font-bold text-gray-800 mb-2">{{ __('ui.leave.to_date') }}</label>
                    <div class="relative">
                        <input type="date" id="to_date" name="to_date" value="{{ old('to_date') }}" required
                               class="appearance-none block w-full min-h-[50px] px-4 py-[13px] pr-10 text-[16px] text-gray-800 bg-white border-2 border-gray-300 @error('to_date') border-red-500 @enderror rounded-xl focus:outline-none focus:border-green-500 transition-colors">
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4">
                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0121 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                            </svg>
                        </div>
                    </div>
                    @error('to_date')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="reason" class="block text-[17px] font-bold text-gray-800 mb-2">{{ __('ui.leave.reason') }}</label>
                <textarea id="reason" name="reason" rows="3" required
                          placeholder="{{ __('ui.leave.reason') }}"
                          class="block w-full min-h-[50px] p-4 text-[17px] text-gray-800 bg-white border-2 border-gray-300 @error('reason') border-red-500 @enderror rounded-xl focus:outline-none focus:border-green-500 transition-colors placeholder-gray-500 resize-none">{{ old('reason') }}</textarea>
                @error('reason')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

        </form>
    </div>

    <div class="space-y-4 pb-6">
        <button type="button" onclick="openModal()"
                class="w-full min-h-[54px] bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-bold text-lg uppercase rounded-xl shadow-md transition-all duration-200 active:scale-[0.98]">
            {{ __('ui.leave.register_page_title') }}
        </button>
        <a href="{{ request('ref') === 'list' ? route('employee.leave.index') : route('employee.dashboard') }}"
           class="flex items-center justify-center w-full min-h-[54px] bg-white border-2 border-gray-300 hover:bg-gray-50 text-gray-600 font-bold text-lg uppercase rounded-xl transition-all duration-200 active:scale-[0.98]">
            {{ __('ui.common.cancel') }}
        </a>
    </div>
</div>

@endsection

@push('modals')
<div id="confirmModal" class="fixed inset-0 bg-black/50 z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4">
    <div id="modalCard" class="bg-white w-full max-w-sm rounded-3xl shadow-2xl p-6 transform scale-95 transition-transform duration-300">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-50 mb-5">
            <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="text-center mb-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-2">{{ __('ui.common.confirm') }}</h3>
            <p class="text-[16px] text-gray-600">{{ __('ui.leave.confirm_submit') }}</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <button type="button" onclick="closeModal()"
                    class="w-full min-h-[50px] bg-white border-2 border-gray-300 text-gray-600 font-bold text-[16px] uppercase rounded-xl active:bg-gray-100 transition-colors">
                {{ __('ui.common.back') }}
            </button>
            <button type="button" onclick="confirmSubmit()"
                    class="w-full min-h-[50px] bg-green-600 text-white font-bold text-[16px] uppercase rounded-xl shadow-md active:bg-green-700 transition-colors">
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
    function confirmSubmit() { document.getElementById('leaveForm').submit(); }
    modal.addEventListener('click', e => { if(e.target === modal) closeModal(); });
</script>
@endpush
