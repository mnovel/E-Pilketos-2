<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CheckinDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'session_id',
        'device_label',
        'device_token',
        'token_expired_at',
        'last_ping_at',
    ];

    protected $casts = [
        'token_expired_at' => 'datetime',
        'last_ping_at'     => 'datetime',
    ];

    // ==================== HELPERS ====================

    public function isTokenValid(): bool
    {
        return $this->token_expired_at && $this->token_expired_at->isFuture();
    }

    public function rotateToken(int $ttlSeconds = 30): void
    {
        $this->update([
            'device_token'     => strtoupper(Str::random(12)),
            'token_expired_at' => now()->addSeconds($ttlSeconds),
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

    public function checkinLogs(): HasMany
    {
        return $this->hasMany(CheckinLog::class);
    }
}
