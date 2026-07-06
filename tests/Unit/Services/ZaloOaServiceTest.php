<?php

namespace Tests\Unit\Services;

use App\Services\ZaloOaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ZaloOaServiceTest extends TestCase
{
    private ZaloOaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.zalo.oa_access_token' => 'test-token']);
        $this->service = new ZaloOaService();
    }

    public function test_sends_message_to_correct_endpoint_with_correct_payload(): void
    {
        Http::fake([
            'openapi.zalo.me/*' => Http::response(['error' => 0], 200),
        ]);

        $result = $this->service->sendTextMessage('user-123', 'Hello Manager');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://openapi.zalo.me/v2.0/oa/message'
                && $request->hasHeader('Authorization', 'OA test-token')
                && $request['recipient']['user_id'] === 'user-123'
                && $request['message']['text'] === 'Hello Manager';
        });
    }

    public function test_returns_true_on_200_success(): void
    {
        Http::fake([
            'openapi.zalo.me/*' => Http::response(['error' => 0], 200),
        ]);

        $result = $this->service->sendTextMessage('user-456', 'Test message');

        $this->assertTrue($result);
    }

    public function test_returns_false_and_logs_on_http_error(): void
    {
        Http::fake([
            'openapi.zalo.me/*' => Http::response('Unauthorized', 401),
        ]);

        Log::shouldReceive('error')->once()->withArgs(function ($message, $context) {
            return str_contains($message, 'ZaloOaService')
                && $context['zalo_user_id'] === 'user-789'
                && $context['status'] === 401;
        });

        $result = $this->service->sendTextMessage('user-789', 'Test message');

        $this->assertFalse($result);
    }

    public function test_returns_false_and_logs_on_connection_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        Log::shouldReceive('error')->once()->withArgs(function ($message, $context) {
            return str_contains($message, 'ZaloOaService')
                && $context['zalo_user_id'] === 'user-999';
        });

        $result = $this->service->sendTextMessage('user-999', 'Test message');

        $this->assertFalse($result);
    }
}
