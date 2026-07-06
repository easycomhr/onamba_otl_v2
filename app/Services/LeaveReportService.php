<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class LeaveReportService
{
    public function getData(array $filters): EloquentCollection
    {
        $query = LeaveRequest::with(['employee' => fn ($q) => $q->select('id', 'name', 'employee_code', 'department')])
            ->select(['leave_requests.id', 'user_id', 'leave_type', 'from_date', 'to_date', 'days', 'status'])
            ->approved();

        if (! empty($filters['from_date']) && ! empty($filters['to_date'])) {
            $query->where('from_date', '<=', $filters['to_date'])
                  ->where('to_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('user_id', $filters['employee_id']);
        }

        if (! empty($filters['department'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department', $filters['department']));
        }

        if (! empty($filters['leave_type'])) {
            $query->where('leave_type', $filters['leave_type']);
        }

        return $query->orderBy('from_date')->get();
    }

    public function getSummary(EloquentCollection $rows): Collection
    {
        return $rows->groupBy('user_id')->map(fn ($rows) => [
            'employee'    => $rows->first()->employee,
            'total_times' => $rows->count(),
            'total_days'  => $rows->sum('days'),
        ])->values();
    }

    public function getDepartments(): array
    {
        return User::where('role', 'employee')
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->toArray();
    }

    public function getLeaveTypes(): array
    {
        return LeaveRequest::LEAVE_TYPES;
    }
}
