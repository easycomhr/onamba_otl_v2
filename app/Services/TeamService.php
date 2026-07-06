<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TeamService
{
    public function list(?string $search = null): LengthAwarePaginator
    {
        return Team::with(['mainApprover:id,name,employee_code', 'subApprovers:id,name,employee_code'])
            ->select(['id', 'name', 'main_approver_id', 'approver_escalation_hours', 'ms_teams_webhook_url'])
            ->withCount('members')
            ->when($search, fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
            ->paginate(15)
            ->withQueryString();
    }

    public function find(int $id): Team
    {
        return Team::with(['mainApprover:id,name', 'subApprovers:id,name,employee_code'])->findOrFail($id);
    }

    public function create(array $data): Team
    {
        return DB::transaction(function () use ($data) {
            $subApproverIds = $data['sub_approver_ids'] ?? [];
            unset($data['sub_approver_ids']);
            $team = Team::create($data);
            if (!empty($subApproverIds)) {
                $team->subApprovers()->sync($subApproverIds);
            }
            return $team;
        });
    }

    public function update(Team $team, array $data): Team
    {
        return DB::transaction(function () use ($team, $data) {
            $subApproverIds = $data['sub_approver_ids'] ?? [];
            unset($data['sub_approver_ids']);
            $team->update($data);
            $team->subApprovers()->sync($subApproverIds);
            return $team->fresh();
        });
    }

    public function delete(Team $team): void
    {
        $team->delete();
    }

    public function getManagerUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return User::select(['id', 'name', 'employee_code', 'role'])
            ->whereIn('role', ['manager', 'employee'])
            ->orderBy('name')
            ->get();
    }

    public function getAllEmployees(): \Illuminate\Database\Eloquent\Collection
    {
        return User::select(['id', 'employee_code', 'name', 'department', 'team_id'])
            ->with('team:id,name')
            ->where('role', User::ROLE_EMPLOYEE)
            ->orderBy('name')
            ->get();
    }

    public function syncMembers(Team $team, array $userIds): void
    {
        DB::transaction(function () use ($team, $userIds) {
            User::where('team_id', $team->id)
                ->whereNotIn('id', $userIds)
                ->update(['team_id' => null]);

            if (!empty($userIds)) {
                User::whereIn('id', $userIds)
                    ->where('role', User::ROLE_EMPLOYEE)
                    ->update(['team_id' => $team->id]);
            }
        });
    }

}
