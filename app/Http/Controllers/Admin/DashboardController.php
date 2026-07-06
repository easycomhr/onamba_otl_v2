<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OtRequest;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        $today = $now->toDateString();

        // Thống kê tổng quan
        $stats = [
            'ot_pending'         => OtRequest::pending()->count(),
            'leave_pending'      => LeaveRequest::pending()->count(),
            'total_employees'    => User::where('role', 'employee')->count(),
            'approved_ot_hours'  => (float) OtRequest::approved()
                                        ->whereYear('ot_date', $now->year)
                                        ->whereMonth('ot_date', $now->month)
                                        ->sum('approved_hours'),
            'approved_leave_days' => (int) LeaveRequest::approved()
                                        ->whereYear('from_date', $now->year)
                                        ->whereMonth('from_date', $now->month)
                                        ->sum('days'),
            'absent_today'       => LeaveRequest::approved()
                                        ->where('from_date', '<=', $today)
                                        ->where('to_date', '>=', $today)
                                        ->count(),
        ];

        // Biểu đồ OT: 6 tháng gần nhất (số giờ approved)
        $otChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $otChart[] = [
                'label' => $month->format('m/Y'),
                'hours' => (float) OtRequest::approved()
                    ->whereYear('ot_date', $month->year)
                    ->whereMonth('ot_date', $month->month)
                    ->sum('approved_hours'),
            ];
        }

        // Biểu đồ Leave: phân bổ theo loại nghỉ (tháng hiện tại)
        $leaveChart = [];
        foreach (LeaveRequest::LEAVE_TYPES as $type => $label) {
            $leaveChart[] = [
                'label' => $label,
                'count' => LeaveRequest::approved()
                    ->where('leave_type', $type)
                    ->whereYear('from_date', $now->year)
                    ->whereMonth('from_date', $now->month)
                    ->count(),
            ];
        }

        // Đơn chờ duyệt gần nhất
        $pendingOt    = OtRequest::with('employee')->pending()->orderByDesc('created_at')->take(5)->get();
        $pendingLeave = LeaveRequest::with('employee')->pending()->orderByDesc('created_at')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'otChart', 'leaveChart', 'pendingOt', 'pendingLeave'));
    }
}