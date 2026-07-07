@extends('layouts.employee')

@section('title', 'Chi tiết lương ' . str_pad($salary['month'], 2, '0', STR_PAD_LEFT) . '/' . $salary['year'] . ' - OTMS')

@section('content')

{{-- Header --}}
<header class="sticky top-0 z-30 bg-white shadow-sm flex items-center p-4 border-b border-gray-100">
    <a href="{{ route('employee.salary.index') }}"
       class="absolute left-4 flex items-center text-gray-500 active:scale-95 active:bg-purple-50 transition-all p-2 -ml-2 rounded-xl">
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
    </a>
    <h1 class="text-[18px] sm:text-xl font-bold text-purple-600 uppercase w-full text-center tracking-wide">
        Chi tiết lương {{ str_pad($salary['month'], 2, '0', STR_PAD_LEFT) }}/{{ $salary['year'] }}
    </h1>
</header>

<main class="flex-1 overflow-y-auto p-4 space-y-5 pb-12">

    {{-- Net pay hero --}}
    <div class="bg-gradient-to-br from-purple-600 to-purple-800 rounded-[1.25rem] shadow-lg p-6 relative overflow-hidden">
        <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-white opacity-10"></div>
        <div class="absolute right-12 -bottom-10 w-24 h-24 rounded-full bg-purple-400 opacity-20"></div>

        <div class="relative z-10 flex flex-col items-center text-center">
            <h2 class="text-purple-100 text-[14px] font-semibold uppercase tracking-wider mb-2">Thực nhận kỳ này</h2>
            <div class="text-white text-4xl sm:text-[40px] font-black tracking-tight mb-5 drop-shadow-md">
                {{ number_format($salary['net']) }} <span class="text-2xl font-bold">VNĐ</span>
            </div>
            <div class="w-full border-t border-purple-400/50 pt-3 flex items-center justify-center gap-3 flex-wrap">
                <div class="flex items-center text-purple-100 text-[13px]">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z"/>
                    </svg>
                    <span>Hình thức: <strong>{{ $salary['payment_method'] }}</strong></span>
                </div>
                @if($salary['parking_free'])
                <span class="bg-purple-400/30 text-purple-100 text-[12px] font-bold px-2.5 py-1 rounded-full border border-purple-300/50">
                    🅿️ Miễn phí đậu xe
                </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Bảo hiểm badges --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
        <p class="text-[13px] font-semibold text-gray-500 uppercase tracking-wider mb-3">Tham gia bảo hiểm</p>
        <div class="flex gap-2 flex-wrap">
            <span class="px-3 py-1.5 rounded-lg text-[13px] font-bold border
                {{ $salary['insurance']['h_included'] ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-100 text-gray-400 border-gray-200 line-through' }}">
                BHXH (8%)
            </span>
            <span class="px-3 py-1.5 rounded-lg text-[13px] font-bold border
                {{ $salary['insurance']['s_included'] ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-100 text-gray-400 border-gray-200 line-through' }}">
                BHYT (1.5%)
            </span>
            <span class="px-3 py-1.5 rounded-lg text-[13px] font-bold border
                {{ $salary['insurance']['j_included'] ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-100 text-gray-400 border-gray-200 line-through' }}">
                BHTN (1%)
            </span>
        </div>
    </div>

    {{-- 1. Thu nhập & Phụ cấp --}}
    <section class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 px-4 py-3.5 border-b border-gray-100 flex items-center">
            <div class="bg-purple-100 p-1.5 rounded-lg mr-3 text-purple-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h3 class="text-[16px] font-bold text-gray-800 uppercase">1. Thu nhập & Phụ cấp</h3>
        </div>
        <div class="p-4 space-y-3.5">
            {{-- Chỉ hiển thị các khoản > 0 --}}
            @foreach($salary['income'] as $row)
                @if($row['value'] > 0)
                <div class="flex justify-between items-center">
                    <span class="text-[14px] text-gray-500 font-medium">{{ $row['label'] }}</span>
                    <span class="text-[16px] font-bold text-gray-900">{{ number_format($row['value']) }}</span>
                </div>
                @endif
            @endforeach
        </div>
        <div class="bg-gray-50/50 px-4 py-3.5 border-t border-gray-200 flex justify-between items-center">
            <span class="text-[15px] font-bold text-gray-800">Cộng thu nhập:</span>
            <span class="text-[18px] font-black text-green-600">{{ number_format($salary['income_total']) }}</span>
        </div>
    </section>

    {{-- 2. Chấm công & Tăng ca --}}
    <section class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 px-4 py-3.5 border-b border-gray-100 flex items-center">
            <div class="bg-blue-100 p-1.5 rounded-lg mr-3 text-blue-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-[16px] font-bold text-gray-800 uppercase">2. Chấm công & Tăng ca</h3>
        </div>
        <div class="p-4 space-y-3.5">
            @foreach($salary['ot'] as $row)
            <div class="flex justify-between items-center">
                <span class="text-[14px] text-gray-500 font-medium">{{ $row['label'] }}</span>
                @if($row['is_money'])
                    <span class="text-[16px] font-bold text-gray-900">{{ number_format($row['value']) }}</span>
                @else
                    <span class="text-[15px] font-bold text-gray-900">{{ $row['value'] }}</span>
                @endif
            </div>
            @endforeach
        </div>
        <div class="bg-gray-50/50 px-4 py-3.5 border-t border-gray-200 flex justify-between items-center">
            <span class="text-[15px] font-bold text-gray-800">Cộng tiền Tăng ca:</span>
            <span class="text-[18px] font-black text-green-600">{{ number_format($salary['ot_total']) }}</span>
        </div>
    </section>

    {{-- 3. Khấu trừ & Tạm ứng --}}
    <section class="bg-red-50 rounded-2xl shadow-sm border border-red-200 overflow-hidden">
        <div class="bg-red-100/60 px-4 py-3.5 border-b border-red-100 flex items-center">
            <div class="bg-red-200 p-1.5 rounded-lg mr-3 text-red-700">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/>
                </svg>
            </div>
            <h3 class="text-[16px] font-bold text-red-800 uppercase">3. Khấu trừ & Tạm ứng</h3>
        </div>
        <div class="p-4 space-y-3.5">
            @foreach($salary['deductions'] as $row)
            @if($row['value'] > 0)
            <div class="flex justify-between items-center">
                <span class="text-[14px] text-red-800/70 font-medium">{{ $row['label'] }}</span>
                <span class="text-[16px] font-bold text-red-600">- {{ number_format($row['value']) }}</span>
            </div>
            @endif
            @endforeach
        </div>
        <div class="bg-red-100/40 px-4 py-3.5 border-t border-red-200 flex justify-between items-center">
            <span class="text-[15px] font-bold text-red-800">Tổng khấu trừ:</span>
            <span class="text-[18px] font-black text-red-700">- {{ number_format($salary['deductions_total']) }}</span>
        </div>
    </section>

    <div class="text-center pt-2">
        <p class="text-gray-400 text-[13px] italic">Nếu có sai sót, vui lòng liên hệ phòng Nhân sự (HR) trước ngày 05 hàng tháng.</p>
    </div>

</main>

@endsection

