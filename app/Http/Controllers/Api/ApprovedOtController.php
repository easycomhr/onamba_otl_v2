<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OtRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApprovedOtController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {

            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            $query = OtRequest::approved()
                ->with(['employee', 'approvedBy']);

            if ($fromDate || $toDate) {
                if ($fromDate) {
                    $query->where('ot_date', '>=', $fromDate);
                }
                if ($toDate) {
                    $query->where('ot_date', '<=', $toDate);
                }
            } else {
                $day = $request->input('day');
                $month = $request->integer('month', now()->month);
                $year = $request->integer('year', now()->year);

                $query->whereYear('ot_date', $year)
                    ->whereMonth('ot_date', $month);

                if ($day !== null) {
                    $query->whereDay('ot_date', (int) $day);
                }
            }

            $mapped = $query->get()
                ->map(fn (OtRequest $r): array => [
                    'employee_code' => $r->employee->employee_code,
                    'employee_name' => $r->employee->name,
                    'department' => $r->employee->department,
                    'position' => $r->employee->position,
                    'ot_date' => $r->ot_date->toDateString(),
                    'hours' => (float) $r->hours,
                    'approved_hours' => (float) $r->approved_hours,
                    'approved_at' => $r->approved_at?->toDateTimeString(),
                    'approved_by_name' => $r->approvedBy?->name,
                    'manager_note' => $r->manager_note,
                ])
                ->values();

            $response = [
                'data' => $mapped,
            ];

            if ($fromDate || $toDate) {
                if ($fromDate) {
                    $response['from_date'] = $fromDate;
                }
                if ($toDate) {
                    $response['to_date'] = $toDate;
                }
            } else {
                $response['month'] = $month;
                $response['year'] = $year;
                if ($day !== null) {
                    $response['day'] = (int) $day;
                }
            }

            return response()->json($response);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch approved OT records.', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Lỗi hệ thống',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
