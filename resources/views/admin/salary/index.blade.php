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
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 mb-5">
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

@if($salaries->isNotEmpty())
{{-- Summary Stats Cards --}}
@php
    $totalIncome     = $salaries->sum(fn($r) => (float) $r->income_total);
    $totalBhxh       = $salaries->sum(fn($r) => (float) $r->deduction_insurance_total);
    $totalAdvance    = $salaries->sum(fn($r) => (float) $r->deduction_advance);
    $totalNet        = $salaries->sum(fn($r) => (float) $r->net);
    $totalDeductions = $salaries->sum(fn($r) =>
        (float) $r->deduction_insurance_total
        + (float) $r->deduction_union_fee
        + (float) $r->deduction_unpaid_leave
        + (float) $r->deduction_pit_regular
        + (float) $r->deduction_pit_irregular
        + (float) $r->deduction_advance
    );
@endphp
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Số nhân viên</p>
        <p class="text-2xl font-black text-gray-800">{{ $salaries->count() }}</p>
        <p class="text-xs text-gray-400 mt-0.5">Kỳ {{ str_pad($month, 2, '0', STR_PAD_LEFT) }}/{{ $year }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Tổng thu nhập</p>
        <p class="text-xl font-black text-green-600">{{ number_format($totalIncome) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">VNĐ</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Tổng khấu trừ</p>
        <p class="text-xl font-black text-red-500">{{ number_format($totalDeductions) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">VNĐ</p>
    </div>
    <div class="bg-purple-600 rounded-2xl shadow-sm px-5 py-4">
        <p class="text-xs font-bold text-purple-200 uppercase tracking-wider mb-1">Tổng thực nhận</p>
        <p class="text-xl font-black text-white">{{ number_format($totalNet) }}</p>
        <p class="text-xs text-purple-200 mt-0.5">VNĐ</p>
    </div>
</div>
@endif

{{-- Table --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-left text-sm" id="salary-table">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3.5 font-semibold text-gray-500 uppercase tracking-wider text-xs w-6"></th>
                <th class="px-4 py-3.5 font-semibold text-gray-500 uppercase tracking-wider text-xs">Mã NV</th>
                <th class="px-4 py-3.5 font-semibold text-gray-500 uppercase tracking-wider text-xs">Họ tên</th>
                <th class="px-4 py-3.5 font-semibold text-gray-500 uppercase tracking-wider text-xs hidden xl:table-cell">Nhóm / Khu vực</th>
                <th class="px-4 py-3.5 font-semibold text-gray-500 uppercase tracking-wider text-xs hidden lg:table-cell">Hình thức</th>
                <th class="px-4 py-3.5 font-semibold text-gray-500 uppercase tracking-wider text-xs text-right hidden md:table-cell">Lương CB</th>
                <th class="px-4 py-3.5 font-semibold text-gray-500 uppercase tracking-wider text-xs text-right hidden lg:table-cell">Tăng ca</th>
                <th class="px-4 py-3.5 font-semibold text-gray-500 uppercase tracking-wider text-xs text-right hidden md:table-cell">Thu nhập</th>
                <th class="px-4 py-3.5 font-semibold text-gray-500 uppercase tracking-wider text-xs text-right hidden lg:table-cell">Bảo hiểm</th>
                <th class="px-4 py-3.5 font-semibold text-gray-500 uppercase tracking-wider text-xs text-right hidden xl:table-cell">Thuế TNCN</th>
                <th class="px-4 py-3.5 font-semibold text-gray-500 uppercase tracking-wider text-xs text-right hidden lg:table-cell">Tạm ứng</th>
                <th class="px-4 py-3.5 font-semibold text-gray-500 uppercase tracking-wider text-xs text-right">Thực nhận</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($salaries as $record)
            @php
                $deductInsurance = (float) $record->deduction_insurance_total;
                $deductPit       = (float) $record->deduction_pit_regular + (float) $record->deduction_pit_irregular;
                $deductAdv       = (float) $record->deduction_advance;
                $rowId           = 'detail-' . $record->id;
            @endphp
            {{-- Main row --}}
            <tr class="hover:bg-purple-50/30 transition-colors cursor-pointer group"
                onclick="toggleDetail('{{ $rowId }}')" id="row-{{ $record->id }}">
                {{-- Expand toggle --}}
                <td class="px-4 py-3.5 text-gray-400 group-hover:text-purple-500 transition-colors">
                    <svg class="w-4 h-4 transition-transform duration-200" id="icon-{{ $record->id }}"
                         fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </td>
                {{-- Mã NV --}}
                <td class="px-4 py-3.5 font-bold text-purple-600 text-sm whitespace-nowrap">
                    {{ $record->employee->employee_code ?? '—' }}
                </td>
                {{-- Họ tên --}}
                <td class="px-4 py-3.5">
                    <div class="font-semibold text-gray-900 text-[14px] whitespace-nowrap">{{ $record->employee->name ?? '—' }}</div>
                    <div class="text-xs text-gray-400">{{ $record->employee->department ?? '' }}</div>
                </td>
                {{-- Nhóm / Khu vực --}}
                <td class="px-4 py-3.5 hidden xl:table-cell">
                    @if($record->group_name || $record->region)
                        <div class="text-[13px] text-gray-700 font-medium">{{ $record->group_name ?: '—' }}</div>
                        <div class="text-xs text-gray-400">{{ $record->region ?: '' }}</div>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                {{-- Hình thức --}}
                <td class="px-4 py-3.5 hidden lg:table-cell">
                    @if($record->payment_method)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold
                            {{ str_contains(strtolower($record->payment_method), 'khoản') || str_contains(strtolower($record->payment_method), 'bank') ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $record->payment_method }}
                        </span>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                {{-- Lương CB --}}
                <td class="px-4 py-3.5 text-right font-semibold text-gray-700 hidden md:table-cell whitespace-nowrap">
                    {{ number_format((float) $record->base_salary) }}
                </td>
                {{-- Tăng ca --}}
                <td class="px-4 py-3.5 text-right hidden lg:table-cell whitespace-nowrap">
                    @if((float) $record->ot_total > 0)
                        <span class="font-semibold text-blue-600">{{ number_format((float) $record->ot_total) }}</span>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                {{-- Thu nhập --}}
                <td class="px-4 py-3.5 text-right font-semibold text-gray-800 hidden md:table-cell whitespace-nowrap">
                    {{ number_format((float) $record->income_total) }}
                </td>
                {{-- Bảo hiểm --}}
                <td class="px-4 py-3.5 text-right hidden lg:table-cell whitespace-nowrap">
                    @if($deductInsurance > 0)
                        <span class="font-semibold text-orange-500">- {{ number_format($deductInsurance) }}</span>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                {{-- Thuế TNCN --}}
                <td class="px-4 py-3.5 text-right hidden xl:table-cell whitespace-nowrap">
                    @if($deductPit > 0)
                        <span class="font-semibold text-amber-600">- {{ number_format($deductPit) }}</span>
                    @else
                        <span class="text-gray-300 text-xs">Không có</span>
                    @endif
                </td>
                {{-- Tạm ứng --}}
                <td class="px-4 py-3.5 text-right hidden lg:table-cell whitespace-nowrap">
                    @if($deductAdv > 0)
                        <span class="font-semibold text-red-500">- {{ number_format($deductAdv) }}</span>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                {{-- Thực nhận --}}
                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                    <span class="font-black text-purple-700 text-[15px]">{{ number_format((float) $record->net) }}</span>
                    <span class="text-xs font-semibold text-gray-400 ml-0.5">VNĐ</span>
                </td>
            </tr>

            {{-- Detail expanded row --}}
            <tr id="{{ $rowId }}" class="hidden bg-purple-50/20">
                <td colspan="12" class="px-6 pb-5 pt-0">
                    <div class="border border-purple-100 rounded-2xl overflow-hidden mt-1">
                        {{-- Detail header --}}
                        <div class="bg-purple-50 px-4 py-2.5 flex items-center justify-between border-b border-purple-100">
                            <span class="text-xs font-bold text-purple-700 uppercase tracking-wider">
                                Chi tiết lương: {{ $record->employee->name ?? '' }} — {{ str_pad($month, 2, '0', STR_PAD_LEFT) }}/{{ $year }}
                            </span>
                            <div class="flex gap-2">
                                @if($record->h_insurance_included)
                                    <span class="text-xs bg-green-100 text-green-700 border border-green-200 px-2 py-0.5 rounded font-semibold">BHXH</span>
                                @endif
                                @if($record->s_insurance_included)
                                    <span class="text-xs bg-green-100 text-green-700 border border-green-200 px-2 py-0.5 rounded font-semibold">BHYT</span>
                                @endif
                                @if($record->j_insurance_included)
                                    <span class="text-xs bg-green-100 text-green-700 border border-green-200 px-2 py-0.5 rounded font-semibold">BHTN</span>
                                @endif
                                @if($record->parking_free)
                                    <span class="text-xs bg-blue-100 text-blue-700 border border-blue-200 px-2 py-0.5 rounded font-semibold">🅿️ Đậu xe</span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                            {{-- Cột 1: Thu nhập & Phụ cấp --}}
                            <div class="p-4">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">💰 Thu nhập & Phụ cấp</p>
                                <div class="space-y-1.5">
                                    @php
                                        $incomeItems = [
                                            ['Lương căn bản',          $record->base_salary],
                                            ['PC Vị trí',              $record->allowance_position],
                                            ['PC Thành thạo',          $record->allowance_proficiency],
                                            ['PC Chỉ đạo/KN',          $record->allowance_steering],
                                            ['PC Môi trường',          $record->allowance_environment],
                                            ['PC CV đặc biệt',         $record->allowance_special_work],
                                            ['PC Hiện trường',         $record->allowance_scene],
                                            ['PC Thiếu nhà ở',         $record->allowance_housing],
                                            ['Khoản thu (Lead)',        $record->allowance_lead],
                                            ['PC Trượt giá',           $record->allowance_slippage],
                                            ['PC Kỹ năng',             $record->allowance_skill],
                                            ['PC Ngôn ngữ',            $record->allowance_language],
                                            ['PC Chuyên cần',          $record->allowance_attendance],
                                            ['PC Cơm trưa',            $record->allowance_lunch],
                                            ['PC Nặng nhọc',           $record->allowance_heavy_work],
                                            ['PC Thâm niên',           $record->allowance_seniority],
                                            ['PC Xăng',                $record->allowance_fuel],
                                            ['PC Đi làm xa',           $record->allowance_distance],
                                            ['PC Điện thoại',          $record->allowance_phone],
                                            ['PC Đứng máy',            $record->allowance_machine],
                                            ['PC Khác',                $record->allowance_other],
                                            ['PC Cơm OT',              $record->allowance_ot_meal],
                                            ['Lương tháng 13',         $record->salary_13th_month],
                                            ['Khoản thường xuyên',     $record->regular_amount],
                                            ['Điều chỉnh',             $record->adjustment],
                                        ];
                                    @endphp
                                    @foreach($incomeItems as [$label, $val])
                                        @if((float) $val > 0)
                                        <div class="flex justify-between items-center text-[13px]">
                                            <span class="text-gray-500">{{ $label }}</span>
                                            <span class="font-semibold text-gray-800">{{ number_format((float) $val) }}</span>
                                        </div>
                                        @endif
                                    @endforeach
                                    <div class="pt-2 mt-1 border-t border-gray-200 flex justify-between items-center text-[13px]">
                                        <span class="font-bold text-gray-700">Cộng thu nhập</span>
                                        <span class="font-black text-green-600">{{ number_format((float) $record->income_total) }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Cột 2: Tăng ca --}}
                            <div class="p-4">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">⏱️ Tăng ca</p>
                                <div class="space-y-1.5">
                                    @if((float) $record->ot_hours_150 > 0)
                                    <div class="flex justify-between items-center text-[13px]">
                                        <span class="text-gray-500">Tăng ca 150%</span>
                                        <span class="font-semibold text-blue-700">{{ number_format((float) $record->ot_hours_150) }}</span>
                                    </div>
                                    @endif
                                    @if((float) $record->ot_hours_200 > 0)
                                    <div class="flex justify-between items-center text-[13px]">
                                        <span class="text-gray-500">Tăng ca 200%</span>
                                        <span class="font-semibold text-blue-700">{{ number_format((float) $record->ot_hours_200) }}</span>
                                    </div>
                                    @endif
                                    @if((float) $record->ot_hours_300 > 0)
                                    <div class="flex justify-between items-center text-[13px]">
                                        <span class="text-gray-500">Tăng ca 300%</span>
                                        <span class="font-semibold text-blue-700">{{ number_format((float) $record->ot_hours_300) }}</span>
                                    </div>
                                    @endif
                                    @if((float) $record->ot_total > 0)
                                    <div class="pt-2 mt-1 border-t border-gray-200 flex justify-between items-center text-[13px]">
                                        <span class="font-bold text-gray-700">Tổng tăng ca</span>
                                        <span class="font-black text-blue-600">{{ number_format((float) $record->ot_total) }}</span>
                                    </div>
                                    @else
                                    <p class="text-gray-400 text-xs italic">Không có tăng ca</p>
                                    @endif
                                </div>

                                {{-- Khấu trừ --}}
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 mt-5">➖ Khấu trừ</p>
                                <div class="space-y-1.5">
                                    @php
                                        $deductItems = [
                                            ['BHXH (8%)',               $record->deduction_bhxh],
                                            ['BHYT (1.5%)',             $record->deduction_bhyt],
                                            ['BHTN (1%)',               $record->deduction_bhtn],
                                            ['Tổng BH (10.5%)',         $record->deduction_insurance_total],
                                            ['Đoàn phí',                $record->deduction_union_fee],
                                            ['Nghỉ không lương',        $record->deduction_unpaid_leave],
                                            ['Thuế TNCN TX',            $record->deduction_pit_regular],
                                            ['Thuế TNCN KTX (10%)',     $record->deduction_pit_irregular],
                                            ['Tạm ứng',                 $record->deduction_advance],
                                        ];
                                    @endphp
                                    @foreach($deductItems as [$label, $val])
                                        @if((float) $val > 0)
                                        <div class="flex justify-between items-center text-[13px]">
                                            <span class="text-gray-500">{{ $label }}</span>
                                            <span class="font-semibold text-red-500">- {{ number_format((float) $val) }}</span>
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            {{-- Cột 3: Tổng kết --}}
                            <div class="p-4">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">📊 Tổng kết</p>
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center text-[13px]">
                                        <span class="text-gray-500">Thu nhập tính thuế</span>
                                        <span class="font-semibold text-gray-700">{{ number_format((float) $record->taxable_income) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[13px]">
                                        <span class="text-gray-500">Thu nhập sau thuế</span>
                                        <span class="font-semibold text-gray-700">{{ number_format((float) $record->income_after_tax) }}</span>
                                    </div>
                                    @if($record->bank_account)
                                    <div class="flex justify-between items-start text-[13px] pt-1">
                                        <span class="text-gray-500">Tài khoản NH</span>
                                        <span class="font-semibold text-gray-700 text-right max-w-[150px] break-all">{{ $record->bank_account }}</span>
                                    </div>
                                    @endif

                                    {{-- Net pay box --}}
                                    <div class="bg-purple-600 rounded-xl p-4 mt-4 text-center">
                                        <p class="text-purple-200 text-[11px] font-semibold uppercase tracking-wider mb-1">Thực nhận</p>
                                        <p class="text-white text-2xl font-black">{{ number_format((float) $record->net) }}</p>
                                        <p class="text-purple-200 text-xs mt-0.5">VNĐ</p>
                                    </div>

                                    {{-- Giảm trừ gia cảnh --}}
                                    @if((float) $record->deduction_personal_exemption > 0 || (float) $record->deduction_dependent_exemption > 0)
                                    <div class="bg-gray-50 rounded-xl p-3 mt-3 space-y-1.5">
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Giảm trừ</p>
                                        @if((float) $record->deduction_personal_exemption > 0)
                                        <div class="flex justify-between text-[12px]">
                                            <span class="text-gray-500">Bản thân</span>
                                            <span class="font-semibold">{{ number_format((float) $record->deduction_personal_exemption) }}</span>
                                        </div>
                                        @endif
                                        @if((float) $record->deduction_dependent_exemption > 0)
                                        <div class="flex justify-between text-[12px]">
                                            <span class="text-gray-500">Gia cảnh</span>
                                            <span class="font-semibold">{{ number_format((float) $record->deduction_dependent_exemption) }}</span>
                                        </div>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="12" class="px-5 py-12 text-center text-gray-400">
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
    &mdash; <span class="text-gray-500">Click vào hàng để xem chi tiết</span>
</div>
@endif

<script>
function toggleDetail(id) {
    const row   = document.getElementById(id);
    const rowId = id.replace('detail-', '');
    const icon  = document.getElementById('icon-' + rowId);

    if (row.classList.contains('hidden')) {
        row.classList.remove('hidden');
        if (icon) icon.style.transform = 'rotate(90deg)';
    } else {
        row.classList.add('hidden');
        if (icon) icon.style.transform = 'rotate(0deg)';
    }
}
</script>

@endsection

