@extends('layouts.admin')

@section('title', 'Import bảng lương')
@section('page_title', 'Import bảng lương')

@section('content')
<h1 class="text-2xl font-extrabold text-gray-900 uppercase tracking-tight mb-6 hidden md:block">Import bảng lương</h1>

<div class="max-w-2xl mx-auto space-y-6">

    <div class="bg-purple-50 border border-purple-200 rounded-2xl p-5 flex items-start">
        <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <div>
            <p class="text-purple-800 font-semibold mb-1">Hướng dẫn import bảng lương:</p>
            <ul class="text-purple-700 text-[14px] space-y-1 list-disc list-inside">
                <li>File phải là định dạng <strong>.xlsx, .xls hoặc .csv</strong></li>
                <li>Kích thước tối đa: <strong>10MB</strong></li>
                <li>Tải file mẫu để xem đúng định dạng cột</li>
                <li>Toàn bộ số liệu được <strong>mã hóa tự động</strong> khi lưu vào hệ thống</li>
                <li>Nếu đã có dữ liệu cùng nhân viên + tháng/năm, sẽ được <strong>cập nhật</strong></li>
            </ul>
        </div>
    </div>

    @if($errors->has('error'))
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
            <p class="text-sm font-semibold text-red-800">{{ $errors->first('error') }}</p>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 px-5 py-4 border-b border-gray-200">
            <h2 class="text-[17px] font-bold text-gray-800 uppercase">Tải lên file bảng lương</h2>
        </div>
        <form method="POST" action="{{ route('admin.salary.import.store') }}" enctype="multipart/form-data" class="p-5 space-y-5">
            @csrf

            {{-- Kỳ lương --}}
            <div>
                <label class="block text-[15px] font-bold text-gray-800 mb-2">Kỳ lương <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <select name="month" required
                                class="block w-full px-4 py-2.5 border border-gray-300 rounded-xl text-[15px] font-semibold text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-purple-500 @error('month') border-red-400 @enderror">
                            <option value="">-- Chọn tháng --</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ old('month') == $m ? 'selected' : '' }}>Tháng {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                            @endfor
                        </select>
                        @error('month')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <select name="year" required
                                class="block w-full px-4 py-2.5 border border-gray-300 rounded-xl text-[15px] font-semibold text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-purple-500 @error('year') border-red-400 @enderror">
                            <option value="">-- Chọn năm --</option>
                            @foreach(range(now()->year, now()->year - 3) as $y)
                                <option value="{{ $y }}" {{ old('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        @error('year')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[15px] font-bold text-gray-800 mb-2">Chọn file <span class="text-red-500">*</span></label>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                       class="block w-full text-[15px] text-gray-800 border-2 border-dashed border-gray-300 rounded-xl px-4 py-6 cursor-pointer hover:border-purple-400 focus:outline-none focus:border-purple-500 transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                @error('file')
                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.salary.template') }}"
                   class="flex items-center px-5 py-3 border-2 border-gray-300 text-gray-600 font-bold rounded-xl hover:bg-gray-50 transition-colors text-[15px]">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    Tải file mẫu
                </a>
                <button type="submit"
                        class="flex-1 min-h-[50px] bg-purple-600 text-white font-bold uppercase rounded-xl shadow-md hover:bg-purple-700 transition-colors">
                    Import bảng lương
                </button>
            </div>
        </form>
    </div>

    @if(session('importResult'))
        @php($importResult = session('importResult'))
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-5 py-4 border-b border-gray-200">
                <h2 class="text-[17px] font-bold text-gray-800 uppercase">Kết quả import</h2>
            </div>
            <div class="p-5 space-y-5">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3">
                        <p class="text-sm font-semibold text-green-800">Thành công: {{ $importResult['success'] ?? 0 }} dòng</p>
                    </div>
                    <div class="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3">
                        <p class="text-sm font-semibold text-yellow-800">Bỏ qua: {{ $importResult['skipped'] ?? 0 }} dòng</p>
                    </div>
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                        <p class="text-sm font-semibold text-red-800">Lỗi: {{ count($importResult['errors'] ?? []) }} dòng</p>
                    </div>
                </div>

                @if(!empty($importResult['errors']))
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Dòng</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Mã NV</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Lý do</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($importResult['errors'] as $error)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $error['row'] ?? '' }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $error['employee_code'] ?? '' }}</td>
                                        <td class="px-4 py-3 text-sm text-red-600">{{ $error['reason'] ?? '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if(($importResult['success'] ?? 0) > 0)
                    <div class="text-center">
                        <a href="{{ route('admin.salary.index') }}"
                           class="inline-flex items-center px-6 py-3 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 transition-colors">
                            Xem bảng lương vừa import →
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>
@endsection
