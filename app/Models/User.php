<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    public const ROLE_EMPLOYEE = 'employee';
    public const ROLE_MANAGER  = 'manager';
    public const ROLE_HR       = 'hr';
    public const ROLE_MASTER_ADMIN_ID       = 1;

    protected $fillable = [
        'name',
        'employee_code',
        'email',
        'password',
        'role',
        'department',
        'position',
        'annual_leave_balance',
        'annual_remain',
        'other_remain',
        'women_remain',
        'hard_remain',
        'compensation_remain',
        'other_balance',
        'women_balance',
        'hard_balance',
        'compensation_balance',
        'zalo_user_id',
        'manager_id',
        'team_id',
        'admin_role_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'annual_leave_balance'  => 'float',
            'annual_remain'         => 'float',
            'other_remain'          => 'float',
            'women_remain'          => 'float',
            'hard_remain'           => 'float',
            'compensation_remain'   => 'float',
            'other_balance'         => 'float',
            'women_balance'         => 'float',
            'hard_balance'          => 'float',
            'compensation_balance'  => 'float',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function team(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function subApproverTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_sub_approvers');
    }

    public function adminRole(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'admin_role_id');
    }

    public function otRequests()
    {
        return $this->hasMany(OtRequest::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    // ── Helpers ────────────────────────────────────────────────

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function isHr(): bool
    {
        return $this->role === self::ROLE_HR;
    }

    public function isMasterAdmin(): bool
    {
        return $this->admin_role_id === self::ROLE_MASTER_ADMIN_ID;
    }

    public function isTeamApprover(): bool
    {
        return Team::where('main_approver_id', $this->id)->exists()
            || $this->subApproverTeams()->exists();
    }

    public function managedTeamIds(): \Illuminate\Support\Collection
    {
        $main = Team::where('main_approver_id', $this->id)->pluck('id');
        $sub  = $this->subApproverTeams()->pluck('teams.id');

        return $main->merge($sub)->unique()->values();
    }

    public function managedTeamMemberIds(): \Illuminate\Support\Collection
    {
        $teamIds = $this->managedTeamIds();
        if ($teamIds->isEmpty()) {
            return collect();
        }

        return User::whereIn('team_id', $teamIds)->pluck('id');
    }

    // ── JWTSubject ─────────────────────────────────────────────

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
