@extends('layouts.employee')

@section('title', __('ui.ot.history_page_title') . ' - OTMS')

@section('content')

<div class="sticky top-0 z-50 bg-white shadow-sm flex items-center p-4">
    <a href="{{ route('employee.dashboard') }}" class="absolute left-4 flex items-center text-gray-600 hover:text-blue-600 active:scale-95 transition-all p-2 -ml-2 rounded-lg">
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
    </a>
    <h1 class="text-[19px] sm:text-xl font-bold text-blue-600 uppercase w-full text-center tracking-wide">{{ __('ui.ot.history_page_title') }}</h1>
    <button onclick="window.location.reload()"
            class="absolute right-4 flex items-center text-gray-600 hover:text-blue-600 active:scale-95 transition-all p-2 -mr-2 rounded-lg"
            title="Reload">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
        </svg>
    </button>
</div>

<div class="flex-1 overflow-y-auto p-4 sm:p-5">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->has('error'))
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 text-sm text-red-700">
            {{ $errors->first('error') }}
        </div>
    @endif

    <div class="mb-6">
        <a href="{{ route('employee.ot.create', ['ref' => 'list']) }}"
           class="flex items-center justify-center w-full min-h-[56px] bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-lg uppercase rounded-xl shadow-md transition-all duration-200 active:scale-[0.98]">
            <svg class="w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            {{ __('ui.ot.register_page_title') }}
        </a>
    </div>

    <div class="space-y-4 pb-6">

        @forelse($requests ?? [] as $req)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 {{ $req->status === 'approved' ? 'relative overflow-hidden' : '' }}">
            @if($req->status === 'approved')
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-green-500 rounded-l-2xl"></div>
            @elseif($req->status === 'rejected')
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-red-500 rounded-l-2xl"></div>
            @endif

            <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-3 {{ in_array($req->status, ['approved','rejected']) ? 'pl-2' : '' }}">
                <span class="text-[17px] font-bold text-gray-800">#{{ $req->code }}</span>
                @php
                    $badge = match($req->status) {
                        'approved' => ['class' => 'bg-green-100 text-green-700', 'label' => __('ui.status.approved')],
                        'rejected' => ['class' => 'bg-red-100 text-red-700', 'label' => __('ui.status.rejected')],
                        default    => ['class' => 'bg-yellow-100 text-yellow-700', 'label' => __('ui.status.pending')],
                    };
                @endphp
                <span class="{{ $badge['class'] }} px-3 py-1 rounded-full text-[14px] font-bold uppercase tracking-wider">
                    {{ $badge['label'] }}
                </span>
            </div>

            <div class="space-y-2.5 text-[16px]">
                <div class="flex justify-between items-start gap-4">
                    <span class="text-gray-500 shrink-0">{{ __('ui.ot.register_date_short') }}:</span>
                    <span class="text-gray-800 font-semibold text-right">{{ $req->ot_date->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between items-start gap-4">
                    <span class="text-gray-500 shrink-0">{{ __('ui.ot.registered_time') }}:</span>
                    <span class="text-gray-800 font-semibold text-right">{{ $req->hours }} {{ __('ui.common.unit_hour') }}</span>
                </div>
                @if($req->status === 'approved')
                <div class="flex justify-between items-start gap-4">
                    <span class="text-gray-600 font-medium shrink-0">{{ __('ui.common.approved_time') }}:</span>
                    <span class="text-blue-600 font-bold text-right">{{ $req->approved_at?->format('d/m/Y H:i') ?? '-' }}</span>
                </div>
                @if($req->manager_note)
                <div class="flex justify-between items-start gap-4g">
                    <span class="text-gray-600 font-medium shrink-0">{{ __('ui.ot.note') }}:</span>
                    <span class="text-blue-700 font-semibold text-right text-[15px]">{{ $req->manager_note }}</span>
                </div>
                @endif
                @elseif($req->status === 'rejected')
                <div class="flex justify-between items-start gap-4">
                    <span class="text-red-600 font-medium shrink-0">{{ __('ui.status.rejected_reason') }}:</span>
                    <span class="text-red-700 font-bold text-right text-[15px]">{{ $req->reject_reason }}</span>
                </div>
                @elseif($req->isOverdue() && $req->status === 'pending' && !$req->hasSos())
                <div class="flex items-start gap-2 bg-yellow-50 -mx-4 px-4 py-2 rounded-lg">
                    <span class="text-yellow-600 shrink-0 text-[15px]">⏰</span>
                    <span class="text-yellow-700 text-[14px] font-medium">{{ __('ui.sos.overdue_notice') }}</span>
                </div>
                @elseif($req->hasSos() && $req->status === 'pending')
                <div class="flex justify-between items-start gap-4 bg-orange-50 -mx-4 px-4 py-2 rounded-lg">
                    <span class="text-orange-600 font-medium shrink-0">🆘 {{ __('ui.sos.sent_at') }}:</span>
                    <span class="text-orange-700 font-bold text-right text-[14px]">{{ $req->sos_requested_at->format('d/m/Y H:i') }}</span>
                </div>
                @endif
            </div>

            @if($req->status === \App\Models\OtRequest::STATUS_PENDING)
            <form id="delete-ot-{{ $req->id }}"
                  method="POST" action="{{ route('employee.ot.destroy', $req) }}"
                  class="mt-3 pt-3 border-t border-gray-100">
                @csrf
                @method('DELETE')
                <button type="button"
                        onclick="confirmDeleteOt('{{ $req->id }}', '{{ $req->code }}')"
                        class="w-full text-center text-sm text-red-600 hover:text-red-700 font-medium py-1.5 rounded-lg hover:bg-red-50 transition-colors duration-150">
                    Xóa đơn
                </button>
            </form>
            @endif
        </div>
        @empty
        {{-- Demo data --}}
            <span class="text-[17px] font-bold text-gray-800 text-center">Không có thông tin đăng ký tăng ca</span>
        @endforelse

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDeleteOt(id, code) {
    Swal.fire({
        title: 'Xóa đơn tăng ca?',
        text: 'Đơn ' + code + ' sẽ bị xóa và không thể khôi phục.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy',
        reverseButtons: true,
    }).then(function(result) {
        if (result.isConfirmed) {
            document.getElementById('delete-ot-' + id).submit();
        }
    });
}
</script>
@endpush
