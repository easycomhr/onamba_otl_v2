@extends('layouts.admin')

@section('title', __('ui.nav.reports_ot'))
@section('page_title', __('ui.nav.reports_ot'))

@section('content')
<h1 class="text-2xl font-extrabold text-gray-900 uppercase tracking-tight mb-6 hidden md:block">{{ __('ui.nav.reports_ot') }}</h1>

<div class="space-y-6">

    {{-- Alerts --}}
    @if($errors->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-4 text-[15px] font-medium">
            {{ $errors->first('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-5 py-4 text-[15px] font-medium">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filter Form (GET) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-blue-50 px-5 py-4 border-b border-blue-100">
            <h2 class="text-[17px] font-bold text-blue-800 uppercase">{{ __('ui.report.filter_title') }}</h2>
        </div>
        <form method="GET" action="{{ route('admin.reports.ot') }}" class="p-5 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
                <div>
                    <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.leave.from_date') }}</label>
                    <input type="date" name="from_date"
                           value="{{ request('from_date', now()->startOfMonth()->format('Y-m-d')) }}"
                           class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                </div>
                <div>
                    <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.leave.to_date') }}</label>
                    <input type="date" name="to_date"
                           value="{{ request('to_date', now()->format('Y-m-d')) }}"
                           class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors">
                </div>
                <div>
                    <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.table.employee') }}</label>
                    <select name="employee_id"
                            class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors bg-white">
                        <option value="">-- {{ __('ui.common.view_all') }} --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected(request('employee_id') == $emp->id)>{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.employee.department') }}</label>
                    <select name="department"
                            class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors bg-white">
                        <option value="">-- {{ __('ui.common.view_all') }} --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept }}" @selected(request('department') == $dept)>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.common.status') }}</label>
                    <select name="status"
                            class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 transition-colors bg-white">
                        <option value="">-- {{ __('ui.common.view_all') }} --</option>
                        <option value="pending" @selected(request('status') === 'pending')>{{ __('ui.status.pending') ?? 'Chờ duyệt' }}</option>
                        <option value="approved" @selected(request('status') === 'approved')>{{ __('ui.status.approved') ?? 'Đã duyệt' }}</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>{{ __('ui.status.rejected') ?? 'Từ chối' }}</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit"
                        class="min-h-[50px] px-8 bg-blue-600 text-white font-bold text-[15px] uppercase rounded-xl shadow-md hover:bg-blue-700 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803a7.5 7.5 0 0010.607 0z"/>
                    </svg>
                    {{ __('ui.common.view_all') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Results --}}
    @if($otRequests->isNotEmpty())

        {{-- Export Form (POST) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-5 py-4 border-b border-gray-100">
                <h2 class="text-[17px] font-bold text-gray-700 uppercase">{{ __('ui.report.export') }}</h2>
            </div>
            <form method="POST" action="{{ route('admin.reports.ot.export') }}" class="p-5">
                @csrf
                <input type="hidden" name="from_date"    value="{{ request('from_date') }}">
                <input type="hidden" name="to_date"      value="{{ request('to_date') }}">
                <input type="hidden" name="employee_id"  value="{{ request('employee_id') }}">
                <input type="hidden" name="department"   value="{{ request('department') }}">
                <input type="hidden" name="status"       value="{{ request('status') }}">
                <div class="flex flex-wrap items-center gap-4">
                    <label class="text-[15px] font-bold text-gray-800">{{ __('ui.common.action') }}:</label>
                    <label class="flex items-center gap-2 text-[15px] text-gray-700 cursor-pointer">
                        <input type="radio" name="format" value="xlsx" checked class="accent-blue-600"> Excel (.xlsx)
                    </label>
                    <label class="flex items-center gap-2 text-[15px] text-gray-700 cursor-pointer">
                        <input type="radio" name="format" value="csv" class="accent-blue-600"> CSV (.csv)
                    </label>
                    <label class="flex items-center gap-2 text-[15px] text-gray-700 cursor-pointer">
                        <input type="radio" name="format" value="pdf" class="accent-blue-600"> PDF (.pdf)
                    </label>
                    <button type="submit"
                            class="ml-auto min-h-[46px] px-7 bg-blue-600 text-white font-bold text-[15px] uppercase rounded-xl shadow-md hover:bg-blue-700 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        {{ __('ui.report.export') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Table 1: Chi tiết tăng ca --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-blue-50 px-5 py-4 border-b border-blue-100">
                <h2 class="text-[17px] font-bold text-blue-800 uppercase">{{ __('ui.ot.detail_title') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="table-auto w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wide">
                            <th class="px-4 py-3 text-left font-semibold">{{ __('ui.employee.code_short') }}</th>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('ui.employee.full_name') }}</th>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('ui.employee.department') }}</th>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('ui.ot.ot_date') }}</th>
                            <th class="px-4 py-3 text-center font-semibold">{{ __('ui.common.status') }}</th>
                            <th class="px-4 py-3 text-right font-semibold">{{ __('ui.ot.approved_hours') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($otRequests as $ot)
                            <tr class="hover:bg-gray-50 transition-colors {{ $loop->even ? 'bg-gray-50/50' : '' }}">
                                <td class="px-4 py-3 text-gray-700 font-mono">{{ $ot->employee->employee_code ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-800 font-medium">{{ $ot->employee->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $ot->employee->department ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($ot->ot_date)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($ot->status === 'approved')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('ui.status.approved') ?? 'Đã duyệt' }}</span>
                                    @elseif($ot->status === 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ __('ui.status.rejected') ?? 'Từ chối' }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ __('ui.status.pending') ?? 'Chờ duyệt' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-800 text-right font-semibold">{{ $ot->status === 'approved' ? $ot->approved_hours : $ot->hours }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Table 2: Tổng hợp theo nhân viên --}}
        @if(!empty($summary))
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-blue-50 px-5 py-4 border-b border-blue-100">
                <h2 class="text-[17px] font-bold text-blue-800 uppercase">{{ __('ui.table.employee') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="table-auto w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wide">
                            <th class="px-4 py-3 text-left font-semibold">{{ __('ui.employee.full_name') }}</th>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('ui.employee.department') }}</th>
                            <th class="px-4 py-3 text-right font-semibold">{{ __('ui.report.total_ot_days') }}</th>
                            <th class="px-4 py-3 text-right font-semibold">{{ __('ui.report.total_ot_hours') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($summary as $row)
                            <tr class="hover:bg-gray-50 transition-colors {{ $loop->even ? 'bg-gray-50/50' : '' }}">
                                <td class="px-4 py-3 text-gray-800 font-medium">{{ $row['employee']->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $row['employee']->department ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-800 text-right">{{ $row['total_days'] }}</td>
                                <td class="px-4 py-3 text-gray-800 text-right font-semibold">{{ $row['total_hours'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    @else
        @if(request()->hasAny(['from_date', 'to_date', 'employee_id', 'department']))
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl px-5 py-4 text-[15px] font-medium">
                {{ __('ui.report.no_data') }}
            </div>
        @endif
    @endif

</div>
@endsection
