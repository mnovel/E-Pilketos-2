<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VotingDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'session_id',
        'device_label',
        'device_token',
        'token_expired_at',
        'assigned_voter_id',
        'assigned_at',
        'status',
        'last_ping_at',
    ];

    protected $casts = [
        'token_expired_at' => 'datetime',
        'assigned_at'      => 'datetime',
        'last_ping_at'     => 'datetime',
        'status'           => DeviceStatus::class,
    ];

    // ==================== HELPERS ====================

    public function isTokenValid(): bool
    {
        return $this->token_expired_at && $this->token_expired_at->isFuture();
    }

    public function isIdle(): bool
    {
        return $this->status === DeviceStatus::IDLE;
    }

    public function isAssigned(): bool
    {
        return $this->status === DeviceStatus::ASSIGNED;
    }

    public function rotateToken(int $ttlSeconds = 30): void
    {
        $this->update([
            'device_token'     => strtoupper(Str::random(12)),
            'token_expired_at' => now()->addSeconds($ttlSeconds),
        ]);
    }

    public function assignTo(Voter $voter): void
    {
        $this->update([
            'assigned_voter_id' => $voter->id,
            'assigned_at'       => now(),
            'status'            => DeviceStatus::ASSIGNED,
        ]);
    }

    public function resetToIdle(): void
    {
        $this->update([
            'assigned_voter_id' => null,
            'assigned_at'       => null,
            'status'            => DeviceStatus::IDLE,
            'device_token'      => strtoupper(Str::random(12)),
            'token_expired_at'  => now()->addSeconds(30),
        ]);
    }

    public function ping(): void
    {
        $this->update(['last_ping_at' => now()]);
    }

    // ==================== RELATIONS ====================

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ElectionSession::class, 'session_id');
    }

    public function assignedVoter(): BelongsTo
    {
        return $this->belongsTo(Voter::class, 'assigned_voter_id');
    }
}
