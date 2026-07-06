<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeamRequest;
use App\Http\Requests\Admin\UpdateTeamRequest;
use App\Models\Team;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    public function __construct(private TeamService $teamService) {}

    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->value();
        $teams  = $this->teamService->list($search ?: null);
        return view('admin.teams.index', compact('teams', 'search'));
    }

    public function create()
    {
        $managers = $this->teamService->getManagerUsers();
        return view('admin.teams.create', compact('managers'));
    }

    public function store(StoreTeamRequest $request)
    {
        try {
            $this->teamService->create($request->validated());
            return redirect()->route('admin.teams.index')->with('success', 'Tạo team thành công.');
        } catch (\Throwable $e) {
            Log::error('TeamController::store failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Tạo team thất bại. Vui lòng thử lại.');
        }
    }

    public function edit(Team $team)
    {
        $team->load(['mainApprover:id,name', 'subApprovers:id,name,employee_code']);
        $managers = $this->teamService->getManagerUsers();
        $members = $team->members()->select(['id', 'employee_code', 'name', 'department'])->orderBy('name')->get();
        $allEmployees = $this->teamService->getAllEmployees();
        return view('admin.teams.edit', compact('team', 'managers', 'members', 'allEmployees'));
    }

    public function update(UpdateTeamRequest $request, Team $team)
    {
        try {
            $this->teamService->update($team, $request->validated());
            return redirect()->route('admin.teams.index')->with('success', 'Cập nhật team thành công.');
        } catch (\Throwable $e) {
            Log::error('TeamController::update failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Cập nhật team thất bại. Vui lòng thử lại.');
        }
    }

    public function destroy(Team $team)
    {
        try {
            $this->teamService->delete($team);
            return redirect()->route('admin.teams.index')->with('success', 'Xóa team thành công.');
        } catch (\Throwable $e) {
            Log::error('TeamController::destroy failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Xóa team thất bại. Vui lòng thử lại.');
        }
    }

    public function syncMembers(Request $request, Team $team)
    {
        $validated = $request->validate([
            'user_ids'   => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        try {
            $this->teamService->syncMembers($team, $validated['user_ids'] ?? []);
            return redirect()->route('admin.teams.edit', $team)
                ->with('success', __('ui.team.members_updated'));
        } catch (\Throwable $e) {
            Log::error('TeamController::syncMembers failed', ['error' => $e->getMessage()]);
            return back()->with('error', __('ui.team.members_update_failed'));
        }
    }
}
