<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'userid'   => 'required|string|max:255',
                'password' => 'required|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first()], 422);
            }

            $credentials = [
                'employee_code' => $request->input('userid'),
                'password'      => $request->input('password'),
            ];

            if (!$token = Auth::guard('api')->attempt($credentials)) {
                \Illuminate\Support\Facades\Log::warning('auth.login_failed', [
                    'userid' => $request->input('userid'),
                    'ip'     => $request->ip(),
                ]);
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $user = Auth::guard('api')->user();

            \Illuminate\Support\Facades\Log::info('auth.login_success', [
                'employee_code' => $user->employee_code,
                'ip'            => $request->ip(),
            ]);

            return response()->json([
                'user_info' => [
                    [
                        'id'            => $user->id,
                        'employee_code' => $user->employee_code,
                        'name'          => $user->name,
                        'email'         => $user->email,
                        'department'    => $user->department,
                        'position'      => $user->position,
                        'role'          => $user->role,
                    ],
                ],
                '_token' => $token,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('auth.login_error', [
                'message' => $e->getMessage(),
                'ip'      => $request->ip(),
            ]);
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
