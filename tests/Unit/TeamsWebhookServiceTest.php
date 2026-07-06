<?php

namespace Tests\Unit;

use App\Models\LeaveRequest;
use App\Models\OtRequest;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamsWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TeamsWebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    private TeamsWebhookService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TeamsWebhookService();
    }

    public function test_send_ot_notification_skips_when_no_webhook_url(): void
    {
        Http::fake();

        $team = $this->makeTeam(null);
        $ot = $this->makeOtRequest();

        $this->service->sendOtNotification($team, $ot);

        Http::assertNothingSent();
    }

    public function test_send_ot_notification_posts_to_webhook_with_correct_payload_structure(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $team = $this->makeTeam('https://example.com/webhook/ot');
        $ot = $this->makeOtRequest();

        $this->service->sendOtNotification($team, $ot);

        Http::assertSent(function ($request) use ($team, $ot) {
            $body = $request->data();
            $message = $body['message'] ?? '';

            return $request->url() === $team->ms_teams_webhook_url
                && array_keys($body) === ['message']
                && str_contains($message, '📢 THÔNG BÁO ĐĂNG KÝ TĂNG CA')
                && str_contains($message, '👤 Nhân viên: ' . $ot->employee->name)
                && str_contains($message, '🏢 Phòng ban: ')
                && str_contains($message, '📅 Ngày tăng ca: ' . $ot->ot_date->format('d/m/Y'))
                && str_contains($message, '📌 Tổng thời gian: ' . $ot->hours . ' giờ')
                && str_contains($message, '📝 Lý do: ' . $ot->reason)
                && str_contains($message, '🔔 Trạng thái: Chờ phê duyệt');
        });
    }

    public function test_send_ot_notification_catches_exception_and_logs_without_throwing(): void
    {
        Http::fake(['*' => fn () => throw new \RuntimeException('connection refused')]);
        Log::spy();

        $team = $this->makeTeam('https://example.com/webhook/ot-error');
        $ot = $this->makeOtRequest();

        $this->service->sendOtNotification($team, $ot);

        Log::shouldHaveReceived('error')->once()->with(
            'Teams webhook OT failed',
            \Mockery::on(function (array $context) use ($team) {
                return $context['team_id'] === $team->id
                    && $context['error'] === 'connection refused';
            })
        );
    }

    public function test_send_leave_notification_skips_when_no_webhook_url(): void
    {
        Http::fake();

        $team = $this->makeTeam(null);
        $leave = $this->makeLeaveRequest();

        $this->service->sendLeaveNotification($team, $leave);

        Http::assertNothingSent();
    }

    public function test_send_leave_notification_posts_to_webhook_with_correct_payload_structure(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $team = $this->makeTeam('https://example.com/webhook/leave');
        $leave = $this->makeLeaveRequest();

        $this->service->sendLeaveNotification($team, $leave);

        Http::assertSent(function ($request) use ($team, $leave) {
            $body = $request->data();

            return $request->url() === $team->ms_teams_webhook_url
                && $body['@type'] === 'MessageCard'
                && $body['summary'] === 'Yêu cầu nghỉ phép mới: ' . $leave->code
                && $body['sections'][0]['activityTitle'] === '🌿 Yêu cầu nghỉ phép mới'
                && $body['sections'][0]['activitySubtitle'] === $leave->employee->name . ' (' . $leave->employee->employee_code . ')'
                && $body['sections'][0]['facts'][0]['name'] === 'Mã yêu cầu'
                && $body['sections'][0]['facts'][0]['value'] === $leave->code
                && $body['sections'][0]['facts'][1]['value'] === LeaveRequest::LEAVE_TYPES[$leave->leave_type]
                && $body['sections'][0]['facts'][6]['value'] === 'Chờ duyệt'
                && $body['themeColor'] === '16A34A';
        });
    }

    public function test_send_leave_notification_catches_exception_and_logs_without_throwing(): void
    {
        Http::fake(['*' => fn () => throw new \RuntimeException('timeout')]);
        Log::spy();

        $team = $this->makeTeam('https://example.com/webhook/leave-error');
        $leave = $this->makeLeaveRequest();

        $this->service->sendLeaveNotification($team, $leave);

        Log::shouldHaveReceived('error')->once()->with(
            'Teams webhook Leave failed',
            \Mockery::on(function (array $context) use ($team) {
                return $context['team_id'] === $team->id
                    && $context['error'] === 'timeout';
            })
        );
    }

    private function makeTeam(?string $webhookUrl): Team
    {
        return Team::create([
            'name' => fake()->unique()->company(),
            'approver_escalation_hours' => 24,
            'ms_teams_webhook_url' => $webhookUrl,
        ]);
    }

    private function makeOtRequest(): OtRequest
    {
        $user = User::factory()->create();

        return OtRequest::create([
            'user_id' => $user->id,
            'code' => 'OT-TEST-' . fake()->unique()->numerify('####'),
            'ot_date' => '2026-03-20',
            'hours' => 2.5,
            'reason' => 'Release support',
            'status' => OtRequest::STATUS_PENDING,
        ]);
    }

    private function makeLeaveRequest(): LeaveRequest
    {
        $user = User::factory()->create();

        return LeaveRequest::create([
            'user_id' => $user->id,
            'code' => 'LV-TEST-' . fake()->unique()->numerify('####'),
            'leave_type' => 'annual',
            'from_date' => '2026-03-23',
            'to_date' => '2026-03-24',
            'days' => 2,
            'reason' => 'Family matter',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);
    }
}
