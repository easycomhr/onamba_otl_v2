@extends('layouts.admin')

@section('title', __('ui.nav.import_leave'))
@section('page_title', __('ui.nav.import_leave'))

@section('content')
<h1 class="text-2xl font-extrabold text-gray-900 uppercase tracking-tight mb-6 hidden md:block">{{ __('ui.nav.import_leave') }}</h1>

<div class="max-w-2xl mx-auto space-y-6">

    <div class="bg-green-50 border border-green-200 rounded-2xl p-5 flex items-start">
        <svg class="w-6 h-6 text-green-600 flex-shrink-0 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <div>
            <p class="text-green-800 font-semibold mb-1">{{ __('ui.import.guide_title') }}</p>
            <ul class="text-green-700 text-[14px] space-y-1 list-disc list-inside">
                <li>{{ __('ui.import.guide_format') }}</li>
                <li>{{ __('ui.import.guide_max_size') }}</li>
                <li>{{ __('ui.import.guide_template') }}</li>
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
            <h2 class="text-[17px] font-bold text-gray-800 uppercase">{{ __('ui.import.upload_file') }}</h2>
        </div>
        <form method="POST" action="{{ route('admin.import.leave.store') }}" enctype="multipart/form-data" class="p-5 space-y-5">
            @csrf
            <div>
                <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.import.upload_file') }} <span class="text-red-500">*</span></label>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                       class="block w-full text-[15px] text-gray-800 border-2 border-dashed border-gray-300 rounded-xl px-4 py-6 cursor-pointer hover:border-green-400 focus:outline-none focus:border-green-500 transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                @error('file')
                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.import.leave.template') }}" class="flex items-center px-5 py-3 border-2 border-gray-300 text-gray-600 font-bold rounded-xl hover:bg-gray-50 transition-colors text-[15px]">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    {{ __('ui.common.view_all') }}
                </a>
                <button type="submit"
                        class="flex-1 min-h-[50px] bg-green-600 text-white font-bold uppercase rounded-xl shadow-md hover:bg-green-700 transition-colors">
                    {{ __('ui.nav.import_leave') }}
                </button>
            </div>
        </form>
    </div>

    @if(session('importResult'))
        @php($importResult = session('importResult'))
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-5 py-4 border-b border-gray-200">
                <h2 class="text-[17px] font-bold text-gray-800 uppercase">{{ __('ui.import.result') }}</h2>
            </div>
            <div class="p-5 space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3">
                        <p class="text-sm font-semibold text-green-800">{{ __('ui.import.success_rows', ['count' => ($importResult['imported'] ?? 0)]) }}</p>
                    </div>
                    <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
                        <p class="text-sm font-semibold text-blue-800">{{ __('ui.import.skipped_rows', ['count' => ($importResult['skipped'] ?? 0)]) }}</p>
                    </div>
                </div>

                @if(!empty($importResult['errors']))
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">{{ __('ui.import.row') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">{{ __('ui.employee.code_short') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">{{ __('ui.common.reason') }}</th>
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
            </div>
        </div>
    @endif

</div>
@endsection
