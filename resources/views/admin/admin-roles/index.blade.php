@extends('layouts.admin')

@section('title', __('ui.admin_role.title'))
@section('page_title', __('ui.admin_role.title'))

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-extrabold text-gray-900 uppercase tracking-tight hidden md:block">{{ __('ui.admin_role.title') }}</h1>
    <a href="{{ route('admin.admin-roles.create') }}"
       class="flex items-center px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-colors text-[15px] shadow-sm">
        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        {{ __('ui.admin_role.create_title') }}
    </a>
</div>

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b border-gray-200">
        <tr>
            <th class="px-5 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm">{{ __('ui.admin_role.name') }}</th>
            <th class="px-5 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm hidden md:table-cell">{{ __('ui.admin_role.description') }}</th>
            <th class="px-5 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm hidden md:table-cell">{{ __('ui.admin_role.modules_label') }}</th>
            <th class="px-5 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm text-center hidden md:table-cell">{{ __('ui.admin_role.users_count') }}</th>
            <th class="px-5 py-4 font-semibold text-gray-500 uppercase tracking-wider text-sm text-center">{{ __('ui.common.action') }}</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
        @forelse($roles as $role)
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-5 py-4 font-bold text-indigo-700">{{ $role->name }}</td>
            <td class="px-5 py-4 text-gray-500 text-sm hidden md:table-cell">{{ $role->description ?? '—' }}</td>
            <td class="px-5 py-4 hidden md:table-cell">
                <div class="flex flex-wrap gap-1">
                    @foreach($role->permissions as $perm)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700">
                            {{ $modules[$perm->module_key] ?? $perm->module_key }}
                        </span>
                    @endforeach
                </div>
            </td>
            <td class="px-5 py-4 text-center text-gray-700 font-semibold hidden md:table-cell">{{ $role->users_count }}</td>
            <td class="px-5 py-4 text-center">
                <div class="flex items-center justify-center gap-2">
                    <a href="{{ route('admin.admin-roles.edit', $role) }}"
                       class="px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-sm font-semibold transition-colors">
                        {{ __('ui.common.edit') }}
                    </a>
                    @if($role->users_count === 0)
                    <form method="POST" action="{{ route('admin.admin-roles.destroy', $role) }}"
                          onsubmit="return confirm(@json(__('ui.admin_role.delete_confirm')))">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 rounded-lg text-sm font-semibold transition-colors">
                            {{ __('ui.common.delete') }}
                        </button>
                    </form>
                    @else
                    <span class="px-3 py-1.5 text-gray-400 text-sm" title="{{ __('ui.admin_role.cannot_delete_has_users') }}">
                        {{ __('ui.common.delete') }}
                    </span>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="px-5 py-12 text-center text-gray-400">{{ __('ui.admin_role.empty') }}</td>
        </tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

@if($roles->hasPages())
<div class="mt-4">{{ $roles->links() }}</div>
@endif
@endsection
