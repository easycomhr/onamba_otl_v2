<?php

namespace App\Services;

use App\Models\OtRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class OtReportService
{
    public function getData(array $filters): EloquentCollection
    {
        $query = OtRequest::with(['employee' => fn ($q) => $q->select('id', 'name', 'employee_code', 'department')])
            ->select(['ot_requests.id', 'user_id', 'ot_date', 'hours', 'approved_hours', 'status']);

        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->whereBetween('ot_date', [$filters['from_date'], $filters['to_date']]);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('user_id', $filters['employee_id']);
        }

        if (!empty($filters['department'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department', $filters['department']));
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('ot_date')->get();
    }

    public function getSummary(EloquentCollection $rows): Collection
    {
        return $rows->groupBy('user_id')->map(fn ($rows) => [
            'employee'    => $rows->first()->employee,
            'total_days'  => $rows->count(),
            'total_hours' => $rows->sum('approved_hours'),
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
}
