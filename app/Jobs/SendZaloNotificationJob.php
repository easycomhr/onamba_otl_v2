<?php

namespace App\Jobs;

use App\Services\ZaloOaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendZaloNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $zaloUserId,
        public string $message,
    ) {}

    public function handle(ZaloOaService $service): void
    {
        $sent = $service->sendTextMessage($this->zaloUserId, $this->message);

        if (! $sent) {
            Log::warning('SendZaloNotificationJob: message not delivered', [
                'zalo_user_id' => $this->zaloUserId,
            ]);
        }
    }
}
