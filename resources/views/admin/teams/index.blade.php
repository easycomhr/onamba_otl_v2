@extends('layouts.admin')

@section('title', __('ui.nav.teams'))
@section('page_title', __('ui.nav.teams'))

@section('content')
<div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-extrabold text-gray-900 uppercase tracking-tight">{{ __('ui.nav.teams') }}</h2>
    <a href="{{ route('admin.teams.create') }}"
       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
        {{ __('ui.team.create_title') }}
    </a>
</div>

<form method="GET" action="{{ route('admin.teams.index') }}" class="mb-4 flex gap-2">
    <input type="text"
           name="search"
           value="{{ $search }}"
           placeholder="{{ __('ui.team.search_placeholder') }}"
           class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition">
    <button type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
        {{ __('ui.common.search') }}
    </button>
    @if($search)
        <a href="{{ route('admin.teams.index') }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
            {{ __('ui.common.reset') }}
        </a>
    @endif
</form>


<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b border-gray-200">
        <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">STT</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('ui.team.name') }}</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('ui.team.main_approver') }}</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('ui.team.sub_approvers') }}</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('ui.team.members') }}</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">MS Teams</th>

            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('ui.common.action') }}</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
        @forelse($teams as $team)
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 text-sm text-gray-600">{{ $loop->iteration }}</td>
                <td class="px-4 py-3 font-semibold text-gray-800">{{ $team->name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $team->mainApprover->name ?? '— ' . __('ui.team.none') . ' —' }}</td>
                <td class="px-4 py-3 text-gray-600">
                    @php
                        $subApproverNames = $team->subApprovers->pluck('name');
                        $visibleNames = $subApproverNames->take(2)->implode(', ');
                        $remainingCount = max($subApproverNames->count() - 2, 0);
                    @endphp
                    @if($subApproverNames->isEmpty())
                        —
                    @else
                        {{ $visibleNames }}
                        @if($remainingCount > 0)
                            + {{ $remainingCount }} {{ __('ui.common.view_all') }}
                        @endif
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-600">
                    <a href="{{ route('admin.teams.edit', $team) }}" class="hover:text-blue-600">
                        {{ __('ui.team.members_count', ['count' => $team->members_count ?? 0]) }}
                    </a>
                </td>
                <td class="px-4 py-3">
                    @if($team->ms_teams_webhook_url)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-green-100 text-green-700">{{ __('ui.common.confirm') }}</span>
                    @else
                        <span class="text-gray-500">—</span>
                    @endif
                </td>

                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.teams.edit', $team) }}"
                           class="px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg text-sm font-semibold transition-colors">
                            {{ __('ui.common.edit') }}
                        </a>
                        <form method="POST" action="{{ route('admin.teams.destroy', $team) }}" onsubmit="return confirm(@json(__('ui.team.delete_confirm')));">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 rounded-lg text-sm font-semibold transition-colors">
                                {{ __('ui.common.delete') }}
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-12 text-gray-400">{{ __('ui.team.empty') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="mt-4">
    {{ $teams->links() }}
</div>
@endsection
