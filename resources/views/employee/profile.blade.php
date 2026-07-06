@extends('layouts.employee')

@section('title', __('ui.profile.title') . ' - OTMS')

@section('content')

<div class="bg-white shadow-sm flex items-center p-4 relative z-10">
    <a href="{{ route('employee.dashboard') }}" class="absolute left-4 flex items-center text-gray-600 hover:text-blue-600 active:scale-95 transition-all p-2 -ml-2 rounded-lg">
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
    </a>
    <h1 class="text-xl font-bold text-gray-800 uppercase w-full text-center">{{ __('ui.profile.title') }}</h1>
</div>

<div class="flex-1 overflow-y-auto p-4 sm:p-5 pb-8">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-5 text-sm text-green-700">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-100 rounded-2xl p-4 mb-5 flex items-start shadow-sm">
        <svg class="w-6 h-6 text-red-500 flex-shrink-0 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.401 3.003c1.155-2 4.043-2 5.198 0l5.598 9.694c1.155 2-.289 4.5-2.599 4.5H6.402c-2.31 0-3.754-2.5-2.599-4.5l5.598-9.694z"/>
        </svg>
        <div class="text-[15px] text-red-800 leading-snug">
            <p class="font-bold mb-1">{{ __('ui.profile.cannot_change_password') }}</p>
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Avatar --}}
    <div class="flex flex-col items-center py-6 mb-2">
        <div class="w-24 h-24 rounded-full bg-blue-100 border-4 border-blue-200 flex items-center justify-center mb-3 shadow-md">
            <svg class="w-14 h-14 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0021.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 003.065 7.097A9.716 9.716 0 0012 21.75a9.716 9.716 0 006.685-2.653zm-12.54-1.285A7.486 7.486 0 0112 15a7.486 7.486 0 015.855 2.812A8.224 8.224 0 0112 20.25a8.224 8.224 0 01-5.855-2.438zM15.75 9a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" clip-rule="evenodd"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">{{ auth()->user()->name ?? 'User' }}</h2>
        <p class="text-gray-500 text-[15px] mt-1">{{ auth()->user()->employee_code ?? 'NV12345' }}</p>
    </div>

    {{-- Info --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
        @foreach([
            ['label'=>__('ui.employee.department'),'value'=> auth()->user()->department ?? '-'],
            ['label'=>__('ui.employee.position'),'value'=> auth()->user()->position ?? '-'],
            ['label'=>'Email','value'=> auth()->user()->email ?? 'nva@company.com'],
            ['label'=>__('ui.leave.balance'),'value'=> ($leaveBalance ?? 5) . ' ' . __('ui.common.unit_day')],
        ] as $item)
        <div class="flex justify-between items-center px-5 py-4 border-b border-gray-50 last:border-0">
            <span class="text-[15px] font-medium text-gray-500">{{ $item['label'] }}</span>
            <span class="text-[16px] font-bold text-gray-800 text-right">{{ $item['value'] }}</span>
        </div>
        @endforeach
    </div>

    {{-- Change password --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <form method="POST" action="{{ route('employee.change-password') }}" class="p-5 space-y-4">
            @csrf
            <h3 class="text-[17px] font-bold text-gray-800 uppercase">{{ __('ui.profile.change_password') }}</h3>
            <div>
                <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.profile.current_password') }}</label>
                <input type="password" name="current_password" required
                       class="block w-full min-h-[50px] px-4 text-[15px] border-2 border-gray-300 @error('current_password') border-red-500 @enderror rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                @error('current_password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.profile.new_password') }}</label>
                <input type="password" name="password" required minlength="8"
                       class="block w-full min-h-[50px] px-4 text-[15px] border-2 border-gray-300 @error('password') border-red-500 @enderror rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.profile.confirm_password') }}</label>
                <input type="password" name="password_confirmation" required minlength="8"
                       class="block w-full min-h-[50px] px-4 text-[15px] border-2 border-gray-300 @error('password_confirmation') border-red-500 @enderror rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                @error('password_confirmation')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="w-full min-h-[50px] bg-blue-600 text-white font-bold uppercase rounded-xl shadow-md hover:bg-blue-700 transition-colors">
                {{ __('ui.profile.change_password') }}
            </button>
        </form>
    </div>

</div>
@endsection
