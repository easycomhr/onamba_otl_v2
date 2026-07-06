<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'main_approver_id',
        'approver_escalation_hours',
        'ms_teams_webhook_url',
    ];

    public function mainApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'main_approver_id');
    }

    public function subApprovers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_sub_approvers');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'team_id');
    }
}
