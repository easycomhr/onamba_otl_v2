@extends('layouts.admin')

@section('title', 'Bảng lương tháng ' . str_pad($month, 2, '0', STR_PAD_LEFT) . '/' . $year)
@section('page_title', 'Bảng lương')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-extrabold text-gray-900 uppercase tracking-tight hidden md:block">Bảng lương</h1>
    <a href="{{ route('admin.salary.import') }}"
       class="flex items-center px-5 py-2.5 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 transition-colors text-[15px] shadow-sm">
        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
        </svg>
        Import bảng lương
    </a>
</div>

{{-- Filter --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('admin.salary.index') }}" class="flex flex-wrap gap-3">
        <select name="month"
                class="border border-gray-300 rounded-xl px-4 py-2.5 text-[15px] font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" @selected($m == $month)>Tháng {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
            @endfor
        </select>

        <select name="year"
                class="border border-gray-300 rounded-xl px-4 py-2.5 text-[15px] font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white">
            @foreach(range(now()->year, now()->year - 3) as $y)
                <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
            @endforeach
        </select>

        <div class="relative flex-1 min-w-[200px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"
                 fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input type="text" name="q" placeholder="Tên hoặc mã NV..." value="{{ request('q') }}"
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-[15px] focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <button type="submit"
                class="px-5 py-2.5 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 transition-colors text-[15px]">
            Tìm kiếm
        </button>
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-5 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm">Mã NV</th>
                <th class="px-5 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm">Họ tên</th>
                <th class="px-5 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm hidden md:table-cell">Hình thức</th>
                <th class="px-5 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm text-right hidden md:table-cell">Thu nhập</th>
                <th class="px-5 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm text-right hidden md:table-cell">Khấu trừ</th>
                <th class="px-5 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm text-right">Thực nhận</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($salaries as $record)
            <tr class="hover:bg-purple-50/40 transition-colors">
                <td class="px-5 py-4 font-bold text-purple-600 text-sm">
                    {{ $record->employee->employee_code ?? '—' }}
                </td>
                <td class="px-5 py-4">
                    <div class="font-semibold text-gray-900 text-[15px]">{{ $record->employee->name ?? '—' }}</div>
                    <div class="text-xs text-gray-400">{{ $record->employee->department ?? '' }}</div>
                </td>
                <td class="px-5 py-4 text-gray-600 text-sm hidden md:table-cell">
                    {{ $record->payment_method ?? '—' }}
                </td>
                <td class="px-5 py-4 text-right text-gray-800 font-semibold text-sm hidden md:table-cell">
                    {{ number_format((float) $record->income_total) }}
                </td>
                <td class="px-5 py-4 text-right text-red-600 font-semibold text-sm hidden md:table-cell">
                    - {{ number_format((float) $record->deductions_total) }}
                </td>
                <td class="px-5 py-4 text-right font-black text-purple-700 text-[16px]">
                    {{ number_format((float) $record->net) }}
                    <span class="text-xs font-semibold text-gray-400 ml-1">VNĐ</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                    Chưa có dữ liệu bảng lương tháng {{ str_pad($month, 2, '0', STR_PAD_LEFT) }}/{{ $year }}.
                    <a href="{{ route('admin.salary.import') }}" class="text-purple-600 font-semibold hover:underline ml-1">Import ngay →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@if($salaries->isNotEmpty())
<div class="mt-4 text-sm text-gray-400 text-right">
    Tổng {{ $salaries->count() }} nhân viên &mdash; Kỳ lương: {{ str_pad($month, 2, '0', STR_PAD_LEFT) }}/{{ $year }}
</div>
@endif

@endsection
