<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZaloOaService
{
    /**
     * Look up a follower's Zalo user_id by phone number.
     * Returns null if not found or user does not follow the OA.
     */
    public function getUserIdByPhone(string $phone): ?string
    {
        $token = config('services.zalo.oa_access_token');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'OA ' . $token,
            ])->get('https://openapi.zalo.me/v2.0/oa/getprofilebyphone', [
                'phone' => $phone,
            ]);

            if ($response->failed()) {
                Log::error('ZaloOaService: failed to get profile by phone', [
                    'phone'  => $phone,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            return $response->json('data.user_id');
        } catch (\Throwable $e) {
            Log::error('ZaloOaService: exception getting profile by phone', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function sendTextMessage(string $zaloUserId, string $text): bool
    {
        $token = config('services.zalo.oa_access_token');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'OA ' . $token,
            ])->post('https://openapi.zalo.me/v2.0/oa/message', [
                'recipient' => ['user_id' => $zaloUserId],
                'message'   => ['text' => $text],
            ]);

            if ($response->failed()) {
                Log::error('ZaloOaService: HTTP error sending message', [
                    'zalo_user_id' => $zaloUserId,
                    'status'       => $response->status(),
                    'body'         => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('ZaloOaService: exception sending message', [
                'zalo_user_id' => $zaloUserId,
                'error'        => $e->getMessage(),
            ]);

            return false;
        }
    }
}
