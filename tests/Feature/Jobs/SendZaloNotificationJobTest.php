<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendZaloNotificationJob;
use App\Services\ZaloOaService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendZaloNotificationJobTest extends TestCase
{
    public function test_job_dispatches_to_queue_without_error(): void
    {
        Queue::fake();

        SendZaloNotificationJob::dispatch('user-123', 'Hello Manager');

        Queue::assertPushed(SendZaloNotificationJob::class, function ($job) {
            return $job->zaloUserId === 'user-123'
                && $job->message === 'Hello Manager';
        });
    }

    public function test_job_handle_calls_zalo_service(): void
    {
        $service = $this->mock(ZaloOaService::class);
        $service->shouldReceive('sendTextMessage')
            ->once()
            ->with('user-123', 'Hello Manager')
            ->andReturn(true);

        $job = new SendZaloNotificationJob('user-123', 'Hello Manager');
        $job->handle($service);
    }

    public function test_job_does_not_throw_when_service_returns_false(): void
    {
        $service = $this->mock(ZaloOaService::class);
        $service->shouldReceive('sendTextMessage')
            ->once()
            ->andReturn(false);

        $job = new SendZaloNotificationJob('user-000', 'Test');
        $job->handle($service);

        // If we reach here, no exception was thrown
        $this->assertTrue(true);
    }
}
