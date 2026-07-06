<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UpdateLeaveBalanceController extends Controller
{
    private const UPDATABLE_FIELDS = [
        'annual_leave_balance',
        'other_balance',
        'women_balance',
        'hard_balance',
        'compensation_balance',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $userId = $request->input('employee_code');
            if (empty($userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The employee_code field is required.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $user = User::where('employee_code', $userId)->firstOrFail();
           
            $data = $request->only(self::UPDATABLE_FIELDS);

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No updatable fields provided',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $rules = collect(self::UPDATABLE_FIELDS)
                ->mapWithKeys(fn (string $field) => [$field => 'sometimes|numeric|min:0'])
                ->all();

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors'  => $validator->errors(),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            DB::transaction(function () use ($user, $data) {
                $user->update($data);
            });

            Log::info('leave_balance.updated', [
                'employee_code' => $user->employee_code,
                'fields'        => array_keys($data),
                'values'        => $data,
                'ip'            => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Updated successfully',
                'data'    => [
                    'employee_code'        => $user->employee_code,
                    'annual_leave_balance' => $user->annual_leave_balance,
                    'other_balance'        => $user->other_balance,
                    'women_balance'        => $user->women_balance,
                    'hard_balance'         => $user->hard_balance,
                    'compensation_balance'  => $user->compensation_balance,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            Log::warning('leave_balance.user_not_found', ['employee_code' => $request->input('employee_code'), 'ip' => $request->ip()]);
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            Log::error('leave_balance.error', ['message' => $e->getMessage(), 'ip' => $request->ip()]);
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
