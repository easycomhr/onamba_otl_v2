@extends('layouts.employee')

@section('title', 'Lịch sử lương - OTMS')

@section('content')

{{-- Header --}}
<div class="sticky top-0 z-30 bg-white shadow-sm flex items-center p-4 border-b border-gray-100">
    <a href="{{ route('employee.dashboard') }}"
       class="absolute left-4 flex items-center text-gray-500 active:scale-95 active:bg-purple-50 transition-all p-2 -ml-2 rounded-xl">
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
    </a>
    <h1 class="text-[19px] sm:text-xl font-bold text-purple-600 uppercase w-full text-center tracking-wide">
        Lịch sử lương
    </h1>
</div>

<div class="flex-1 overflow-y-auto pb-12">

    {{-- Filter --}}
    <div class="p-4 pt-5">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3">
            <div class="grid grid-cols-5 gap-2.5">
                <div class="col-span-2 relative">
                    <select id="filter-month" class="block w-full min-h-[50px] px-3 pr-8 bg-gray-50 border border-gray-200 rounded-xl text-[15px] font-bold text-gray-700 focus:outline-none appearance-none cursor-pointer">
                        @php($months = $salaries->pluck('month')->unique()->sortDesc()->values())
                        @foreach($months as $m)
                            <option value="{{ $m }}">Tháng {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                        @endforeach
                        @if($months->isEmpty())
                            <option value="">-- Tất cả --</option>
                        @endif
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>

                <div class="col-span-2 relative">
                    <select id="filter-year" class="block w-full min-h-[50px] px-3 pr-8 bg-gray-50 border border-gray-200 rounded-xl text-[15px] font-bold text-gray-700 focus:outline-none appearance-none cursor-pointer">
                        @php($years = $salaries->pluck('year')->unique()->sortDesc()->values())
                        @foreach($years as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                        @if($years->isEmpty())
                            <option value="{{ now()->year }}">{{ now()->year }}</option>
                        @endif
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>

                <button type="button" id="btn-filter"
                        class="col-span-1 bg-purple-600 hover:bg-purple-700 text-white rounded-xl min-h-[50px] flex items-center justify-center shadow-sm transition-colors active:scale-95">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>


    {{-- Salary list --}}
    <div class="px-4 space-y-5">
        @foreach($salaries as $item)
        <a href="{{ route('employee.salary.show', ['month' => $item['month'], 'year' => $item['year']]) }}"
           class="block bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden active:scale-[0.98] transition-transform">

            <div class="bg-purple-50 border-b border-purple-100 p-4 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="bg-purple-200 p-2 rounded-full text-purple-700 mr-3">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                        </svg>
                    </div>
                    <span class="font-bold text-purple-800 text-[17px] uppercase tracking-wide">
                        Kỳ lương: Tháng {{ str_pad($item['month'], 2, '0', STR_PAD_LEFT) }}/{{ $item['year'] }}
                    </span>
                </div>
                <svg class="w-5 h-5 text-purple-300" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </div>

            <div class="p-5 space-y-3.5">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 font-medium text-[15px]">Tổng thu nhập</span>
                    <span class="font-bold text-gray-800 text-[16px]">{{ number_format($item['total_income']) }} VNĐ</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 font-medium text-[15px]">Tạm ứng</span>
                    @if($item['advance'] > 0)
                        <span class="font-bold text-red-500 text-[16px]">- {{ number_format($item['advance']) }} VNĐ</span>
                    @else
                        <span class="font-bold text-gray-400 text-[16px]">0 VNĐ</span>
                    @endif
                </div>
                <div class="flex justify-between items-center pt-1">
                    <span class="text-gray-500 font-medium text-[15px]">Trạng thái</span>
                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-lg text-[13px] font-bold uppercase tracking-wider border border-green-200">
                        Đã thanh toán
                    </span>
                </div>
            </div>

            <div class="bg-gray-50 px-5 py-4 border-t border-gray-200 flex justify-between items-center">
                <div class="flex flex-col">
                    <span class="text-[13px] text-gray-500 font-medium mb-1">Hình thức:</span>
                    <span class="text-[14px] text-gray-800 font-semibold">{{ $item['payment_method'] }}</span>
                </div>
                <div class="text-right">
                    <div class="text-[13px] text-purple-600 font-bold mb-1 uppercase tracking-wider">Thực nhận</div>
                    <div class="text-[22px] font-black text-purple-700 leading-none tracking-tight">{{ number_format($item['net']) }} VNĐ</div>
                </div>
            </div>
        </a>
        @endforeach
    </div>

</div>

@endsection
