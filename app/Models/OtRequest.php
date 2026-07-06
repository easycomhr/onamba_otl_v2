<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtRequest extends Model
{
    use HasFactory, SoftDeletes;

    const SOS_HOURS = 32;

    protected $fillable = [
        'user_id',
        'approved_by',
        'code',
        'ot_date',
        'hours',
        'approved_hours',
        'reason',
        'manager_note',
        'status',
        'rejected_at',
        'approved_at',
        'sos_requested_at',
        'sos_reason',
    ];

    protected function casts(): array
    {
        return [
            'ot_date'          => 'date',
            'hours'            => 'decimal:1',
            'approved_hours'   => 'decimal:1',
            'rejected_at'      => 'datetime',
            'approved_at'      => 'datetime',
            'sos_requested_at' => 'datetime',
        ];
    }

    public function isOverdue(): bool
    {
        return $this->created_at->diffInHours(now()) >= self::SOS_HOURS;
    }

    public function hasSos(): bool
    {
        return $this->sos_requested_at !== null;
    }

    public function isSosEligible(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->isOverdue() && !$this->hasSos();
    }

    // ── Status constants ───────────────────────────────────────

    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // ── Boot: auto-generate code ───────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (OtRequest $model) {
            if (empty($model->code)) {
                $year  = now()->format('Y');
                $month = now()->format('m');
                $seq   = static::withTrashed()
                               ->whereYear('created_at', $year)
                               ->whereMonth('created_at', $month)
                               ->count() + 1;
                $model->code = sprintf('OT-%s%s-%02d', $year, $month, $seq);
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    // ── Accessors ──────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Đã duyệt',
            self::STATUS_REJECTED => 'Từ chối',
            default               => 'Chờ duyệt',
        };
    }
}
