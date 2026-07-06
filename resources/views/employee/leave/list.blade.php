@extends('layouts.employee')

@section('title', __('ui.leave.history_page_title') . ' - OTMS')

@section('content')

<div class="sticky top-0 z-50 bg-white shadow-sm flex items-center p-4">
    <a href="{{ route('employee.dashboard') }}" class="absolute left-4 flex items-center text-gray-600 hover:text-green-600 active:scale-95 transition-all p-2 -ml-2 rounded-lg">
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
    </a>
    <h1 class="text-[19px] sm:text-xl font-bold text-green-600 uppercase w-full text-center tracking-wide">{{ __('ui.leave.history_page_title') }}</h1>
    <button onclick="window.location.reload()"
            class="absolute right-4 flex items-center text-gray-600 hover:text-green-600 active:scale-95 transition-all p-2 -mr-2 rounded-lg"
            title="Reload">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
        </svg>
    </button>
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

    <div class="mb-6">
        <a href="{{ route('employee.leave.create', ['ref' => 'list']) }}"
           class="flex items-center justify-center w-full min-h-[56px] bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-bold text-lg uppercase rounded-xl shadow-md transition-all duration-200 active:scale-[0.98]">
            <svg class="w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            {{ __('ui.leave.register_page_title') }}
        </a>
    </div>

    <div class="space-y-4 pb-6">

        @forelse($requests ?? [] as $req)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 relative overflow-hidden">
            @if($req->status === 'approved')
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-green-500 rounded-l-2xl"></div>
            @elseif($req->status === 'rejected')
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-red-500 rounded-l-2xl"></div>
            @endif
            <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-3 {{ $req->status !== 'pending' ? 'pl-2' : '' }}">
                <span class="text-[17px] font-bold text-gray-800">#{{ $req->code }}</span>
                @php
                    $badge = match($req->status) {
                        'approved' => ['class'=>'bg-green-100 text-green-700','label'=>__('ui.status.approved')],
                        'rejected' => ['class'=>'bg-red-100 text-red-700','label'=>__('ui.status.rejected')],
                        default    => ['class'=>'bg-yellow-100 text-yellow-700','label'=>__('ui.status.pending')],
                    };
                @endphp
                <span class="{{ $badge['class'] }} px-3 py-1 rounded-full text-[14px] font-bold uppercase">{{ $badge['label'] }}</span>
            </div>
            <div class="space-y-2.5 text-[16px]">
                <div class="flex justify-between"><span class="text-gray-500">{{ __('ui.leave.type_short') }}:</span><span class="font-semibold">{{ $req->leave_type_label }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('ui.leave.from_date') }}:</span><span class="font-semibold">{{ $req->from_date->format('d/m/Y') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('ui.leave.to_date') }}:</span><span class="font-semibold">{{ $req->to_date->format('d/m/Y') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('ui.leave.total_days') }}:</span><span class="font-bold text-green-600">{{ $req->days }} {{ __('ui.common.unit_day') }}</span></div>
                @if($req->status === 'rejected')
                <div class="flex justify-between items-start gap-4 bg-red-50 -mx-4 px-4 py-2 rounded-lg">
                    <span class="text-red-600 font-medium">{{ __('ui.status.rejected_reason') }}:</span>
                    <span class="text-red-700 font-bold text-right text-[15px]">{{ $req->reject_reason }}</span>
                </div>
                @elseif($req->isOverdue() && $req->status === 'pending' && !$req->hasSos())
                <div class="flex items-start gap-2 bg-yellow-50 -mx-4 px-4 py-2 rounded-lg">
                    <span class="text-yellow-600 shrink-0 text-[15px]">⏰</span>
                    <span class="text-yellow-700 text-[14px] font-medium">{{ __('ui.sos.overdue_notice') }}</span>
                </div>
                @elseif($req->hasSos() && $req->status === 'pending')
                <div class="flex justify-between items-start gap-4 bg-orange-50 -mx-4 px-4 py-2 rounded-lg">
                    <span class="text-orange-600 font-medium shrink-0">🆘 {{ __('ui.sos.sent_at') }}:</span>
                    <span class="text-orange-700 font-bold text-right text-[14px]">{{ $req->sos_requested_at->format('d/m/Y H:i') }}</span>
                </div>
                @endif
            </div>
        </div>
        @empty
        {{-- Demo data --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5">
            <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-3">
                <span class="text-[17px] font-bold text-gray-800">#LV-001</span>
                <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-[14px] font-bold uppercase">{{ __('ui.status.pending') }}</span>
            </div>
            <div class="space-y-2.5 text-[16px]">
                <div class="flex justify-between"><span class="text-gray-500">{{ __('ui.leave.type_short') }}:</span><span class="font-semibold">{{ __('ui.leave.type.annual') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('ui.leave.from_date') }}:</span><span class="font-semibold">18/03/2026</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('ui.leave.to_date') }}:</span><span class="font-semibold">19/03/2026</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('ui.leave.total_days') }}:</span><span class="font-bold text-green-600">2 {{ __('ui.common.unit_day') }}</span></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-green-500 rounded-l-2xl"></div>
            <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-3 pl-2">
                <span class="text-[17px] font-bold text-gray-800">#LV-002</span>
                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-[14px] font-bold uppercase">{{ __('ui.status.approved') }}</span>
            </div>
            <div class="space-y-2.5 text-[16px] pl-2">
                <div class="flex justify-between"><span class="text-gray-500">{{ __('ui.leave.type_short') }}:</span><span class="font-semibold">{{ __('ui.leave.type.sick') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('ui.leave.from_date') }}:</span><span class="font-semibold">05/03/2026</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('ui.leave.to_date') }}:</span><span class="font-semibold">05/03/2026</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('ui.leave.total_days') }}:</span><span class="font-bold text-green-600">1 {{ __('ui.common.unit_day') }}</span></div>
            </div>
        </div>
        @endforelse

    </div>
</div>

@endsection
