@extends('layouts.admin')

@section('title', __('ui.admin_role.edit_title'))
@section('page_title', __('ui.admin_role.edit_title'))

@section('content')
<nav class="mb-6">
    <a href="{{ route('admin.admin-roles.index') }}" class="flex items-center text-gray-500 hover:text-indigo-600 transition-colors w-fit">
        <svg class="w-6 h-6 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
        <span class="font-semibold">{{ __('ui.common.back_to_list') }}</span>
    </a>
</nav>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 px-5 py-4 border-b border-gray-200">
            <h2 class="text-[17px] font-bold text-gray-800 uppercase tracking-wide">{{ __('ui.admin_role.edit_title') }}: {{ $adminRole->name }}</h2>
        </div>

        <form method="POST" action="{{ route('admin.admin-roles.update', $adminRole) }}" class="p-5 space-y-6">
            @csrf @method('PUT')

            {{-- Name --}}
            <div>
                <label class="block text-[15px] font-bold text-gray-800 mb-2">
                    {{ __('ui.admin_role.name') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $adminRole->name) }}" required maxlength="100"
                       class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-indigo-500 transition-colors">
                @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-[15px] font-bold text-gray-800 mb-2">{{ __('ui.admin_role.description') }}</label>
                <input type="text" name="description" value="{{ old('description', $adminRole->description) }}" maxlength="255"
                       class="block w-full min-h-[50px] px-4 text-[15px] text-gray-800 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-indigo-500 transition-colors">
                @error('description')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Modules --}}
            <div>
                <label class="block text-[15px] font-bold text-gray-800 mb-3">{{ __('ui.admin_role.modules_label') }}</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 border border-gray-200 rounded-xl p-4 bg-gray-50">
                    @foreach($modules as $key => $label)
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-white cursor-pointer transition-colors">
                        <input type="checkbox" name="modules[]" value="{{ $key }}"
                               {{ in_array($key, old('modules', $checkedKeys)) ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-[14px] text-gray-700 font-medium">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
                @error('modules')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3 pt-2">
                <a href="{{ route('admin.admin-roles.index') }}"
                   class="flex-1 flex items-center justify-center min-h-[50px] bg-white border-2 border-gray-300 text-gray-600 font-bold uppercase rounded-xl hover:bg-gray-50 transition-colors">
                    {{ __('ui.common.cancel') }}
                </a>
                <button type="submit"
                        class="flex-1 min-h-[50px] bg-indigo-600 text-white font-bold uppercase rounded-xl shadow-md hover:bg-indigo-700 transition-colors">
                    {{ __('ui.common.save') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
