<x-mail::message>
# Xin chào {{ $otRequest->employee->name }},

Đơn đăng ký tăng ca (OT) của bạn vào ngày **{{ $otRequest->ot_date->format('d/m/Y') }}** đã được xử lý.

**Trạng thái:** {{ $otRequest->status_label }}

@if($otRequest->status === \App\Models\OtRequest::STATUS_APPROVED)
**Số giờ duyệt:** {{ $otRequest->approved_hours }} giờ
@endif

@if($otRequest->manager_note)
**Ghi chú từ quản lý:**
{{ $otRequest->manager_note }}
@endif

<x-mail::button :url="config('app.url') . '/employee/ot'">
Xem lịch sử OT
</x-mail::button>

Trân trọng,<br>
{{ config('app.name') }}
</x-mail::message>
