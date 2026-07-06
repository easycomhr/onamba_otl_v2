<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\OtRequest;
use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TeamsWebhookService
{
    public function sendOtNotification(Team $team, OtRequest $ot): void
    {
        if (!$team->ms_teams_webhook_url) {
            return;
        }

        try {
            $ot->loadMissing('employee');
            Http::timeout(5)->post($team->ms_teams_webhook_url, $this->buildOtPayload($ot));
        } catch (\Throwable $e) {
            Log::error('Teams webhook OT failed', [
                'team_id' => $team->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendLeaveNotification(Team $team, LeaveRequest $leave): void
    {
        if (!$team->ms_teams_webhook_url) {
            return;
        }

        try {
            $leave->loadMissing('employee');
            Http::timeout(5)->post($team->ms_teams_webhook_url, $this->buildLeavePayload($leave));
        } catch (\Throwable $e) {
            Log::error('Teams webhook Leave failed', [
                'team_id' => $team->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendAdminSosOt(OtRequest $ot): void
    {
        $url = config('services.teams.admin_webhook_url');
        if (!$url) {
            return;
        }

        try {
            $ot->loadMissing('employee');
            Http::timeout(5)->post($url, [
                '@type'    => 'MessageCard',
                '@context' => 'http://schema.org/extensions',
                'themeColor' => 'DC2626',
                'summary'  => '🆘 Yêu cầu SOS OT: ' . $ot->code,
                'sections' => [[
                    'activityTitle'    => '🆘 Yêu cầu hỗ trợ SOS — Tăng ca',
                    'activitySubtitle' => ($ot->employee->name ?? '') . ' (' . ($ot->employee->employee_code ?? '') . ')',
                    'facts' => [
                        ['name' => 'Mã yêu cầu', 'value' => $ot->code],
                        ['name' => 'Ngày OT',    'value' => $ot->ot_date->format('d/m/Y')],
                        ['name' => 'Số giờ',     'value' => $ot->hours . ' giờ'],
                        ['name' => 'Lý do SOS',  'value' => $ot->sos_reason ?? '-'],
                        ['name' => 'Gửi lúc',    'value' => $ot->sos_requested_at?->format('d/m/Y H:i') ?? '-'],
                    ],
                ]],
            ]);
        } catch (\Throwable $e) {
            Log::error('Teams admin SOS OT webhook failed', ['ot_id' => $ot->id, 'error' => $e->getMessage()]);
        }
    }

    public function sendAdminSosLeave(LeaveRequest $leave): void
    {
        $url = config('services.teams.admin_webhook_url');
        if (!$url) {
            return;
        }

        try {
            $leave->loadMissing('employee');
            $typeLabel = LeaveRequest::LEAVE_TYPES[$leave->leave_type] ?? $leave->leave_type;
            Http::timeout(5)->post($url, [
                '@type'    => 'MessageCard',
                '@context' => 'http://schema.org/extensions',
                'themeColor' => 'DC2626',
                'summary'  => '🆘 Yêu cầu SOS Nghỉ phép: ' . $leave->code,
                'sections' => [[
                    'activityTitle'    => '🆘 Yêu cầu hỗ trợ SOS — Nghỉ phép',
                    'activitySubtitle' => ($leave->employee->name ?? '') . ' (' . ($leave->employee->employee_code ?? '') . ')',
                    'facts' => [
                        ['name' => 'Mã yêu cầu', 'value' => $leave->code],
                        ['name' => 'Loại nghỉ',  'value' => $typeLabel],
                        ['name' => 'Từ ngày',     'value' => $leave->from_date->format('d/m/Y')],
                        ['name' => 'Đến ngày',    'value' => $leave->to_date->format('d/m/Y')],
                        ['name' => 'Lý do SOS',   'value' => $leave->sos_reason ?? '-'],
                        ['name' => 'Gửi lúc',     'value' => $leave->sos_requested_at?->format('d/m/Y H:i') ?? '-'],
                    ],
                ]],
            ]);
        } catch (\Throwable $e) {
            Log::error('Teams admin SOS Leave webhook failed', ['leave_id' => $leave->id, 'error' => $e->getMessage()]);
        }
    }

    private function buildOtPayload(OtRequest $ot): array
    {
        $date = $ot->ot_date instanceof \Carbon\Carbon
            ? $ot->ot_date->format('d/m/Y')
            : (string) $ot->ot_date;

        $details = implode("\n\n", [
            '👤 Nhân viên: ' . ($ot->employee->name ?? '-'),
            '🏢 Phòng ban: ' . ($ot->employee->department ?? '-'),
            '📅 Ngày tăng ca: ' . $date,
            '📌 Tổng thời gian: ' . $ot->hours . ' giờ',
            '📝 Lý do: ' . ($ot->reason ?? '-'),
        ]);

        $message = "📢 THÔNG BÁO ĐĂNG KÝ TĂNG CA\n\n{$details}\n\n🔔 Trạng thái: Chờ phê duyệt";

        return ['message' => $message];
    }

    private function buildLeavePayload(LeaveRequest $leave): array
    {
        $typeLabel = LeaveRequest::LEAVE_TYPES[$leave->leave_type] ?? $leave->leave_type;

        return [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'themeColor' => '16A34A',
            'summary' => 'Yêu cầu nghỉ phép mới: ' . ($leave->leave_code ?? $leave->code ?? ''),
            'sections' => [[
                'activityTitle' => '🌿 Yêu cầu nghỉ phép mới',
                'activitySubtitle' => ($leave->employee->name ?? '') . ' (' . ($leave->employee->employee_code ?? '') . ')',
                'facts' => [
                    ['name' => 'Mã yêu cầu', 'value' => $leave->leave_code ?? $leave->code ?? '-'],
                    ['name' => 'Loại nghỉ', 'value' => $typeLabel],
                    ['name' => 'Từ ngày', 'value' => (string) $leave->from_date],
                    ['name' => 'Đến ngày', 'value' => (string) $leave->to_date],
                    ['name' => 'Số ngày', 'value' => $leave->days . ' ngày'],
                    ['name' => 'Lý do', 'value' => $leave->reason ?? '-'],
                    ['name' => 'Trạng thái', 'value' => 'Chờ duyệt'],
                ],
            ]],
        ];
    }
}
