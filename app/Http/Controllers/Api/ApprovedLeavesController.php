<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\LegacyLeaveTypeMapper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ApprovedLeavesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $fromDate = $request->input('from_date');
            $toDate   = $request->input('to_date');
            $userId   = $request->input('user_id');

            $query = LeaveRequest::approved()
                ->with(['employee', 'approvedBy']);

            // Filter theo user_id (độc lập với chế độ date)
            if ($userId !== null) {
                $query->where('user_id', $userId);
            }

            // Nếu có from_date / to_date → filter overlap range
            // Điều kiện overlap: leave.from_date <= toDate AND leave.to_date >= fromDate
            if ($fromDate || $toDate) {
                if ($fromDate) {
                    $query->where('to_date', '>=', $fromDate);
                }
                if ($toDate) {
                    $query->where('from_date', '<=', $toDate);
                }
            } else {
                // Fallback: filter theo month/year
                $month = $request->integer('month', now()->month);
                $year  = $request->integer('year', now()->year);

                $query->where(function ($q) use ($month, $year) {
                    $q->where(function ($sub) use ($month, $year) {
                        $sub->whereYear('from_date', $year)
                            ->whereMonth('from_date', $month);
                    })->orWhere(function ($sub) use ($month, $year) {
                        $sub->whereYear('to_date', $year)
                            ->whereMonth('to_date', $month);
                    });
                });
            }

            $mapped = $query->get()
                ->map(fn (LeaveRequest $leaveRequest): array => [
                    'legacy_leavetype_id' => LegacyLeaveTypeMapper::toLegacyId($leaveRequest->leave_type),
                    'leave_type_key'      => $leaveRequest->leave_type,
                    'leave_type_label'    => LeaveRequest::LEAVE_TYPES[$leaveRequest->leave_type] ?? $leaveRequest->leave_type,
                    'employee_code'       => $leaveRequest->employee->employee_code,
                    'employee_name'       => $leaveRequest->employee->name,
                    'department'          => $leaveRequest->employee->department,
                    'position'            => $leaveRequest->employee->position,
                    'from_date'           => $leaveRequest->from_date->toDateString(),
                    'to_date'             => $leaveRequest->to_date->toDateString(),
                    'days'                => $leaveRequest->days,
                    'reason'              => $leaveRequest->reason,
                    'approved_at'         => $leaveRequest->approved_at?->toDateTimeString(),
                    'approved_by_name'    => $leaveRequest->approvedBy?->name,
                    'otlms_code'          => $leaveRequest->code,
                ])
                ->values();

            // Xây dựng response meta
            $response = ['data' => $mapped];

            if ($fromDate || $toDate) {
                if ($fromDate) $response['from_date'] = $fromDate;
                if ($toDate)   $response['to_date']   = $toDate;
            } else {
                $response['month'] = $request->integer('month', now()->month);
                $response['year']  = $request->integer('year', now()->year);
            }

            if ($userId !== null) {
                $response['user_id'] = $userId;
            }

            return response()->json($response);
        } catch (\Throwable $exception) {
            Log::error('Failed to fetch approved leaves for legacy integration', [
                'from_date' => $request->input('from_date'),
                'to_date'   => $request->input('to_date'),
                'user_id'   => $request->input('user_id'),
                'month'     => $request->integer('month', now()->month),
                'year'      => $request->integer('year', now()->year),
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Lỗi hệ thống',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
