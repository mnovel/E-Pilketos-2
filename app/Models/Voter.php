<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Voter extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'session_id',
        'user_id',
        'class_id',
        'qr_token',
        'checked_in',
        'checked_in_at',
        'has_voted',
        'voted_at',
    ];

    protected $casts = [
        'checked_in'    => 'boolean',
        'checked_in_at' => 'datetime',
        'has_voted'     => 'boolean',
        'voted_at'      => 'datetime',
    ];

    // ==================== BOOT ====================

    protected static function booted(): void
    {
        static::creating(function (Voter $voter) {
            if (empty($voter->qr_token)) {
                $voter->qr_token = self::generateQrToken();
            }
        });
    }

    public static function generateQrToken(): string
    {
        do {
            $token = 'PLK-' . strtoupper(Str::random(20));
        } while (self::where('qr_token', $token)->exists());

        return $token;
    }

    // ==================== HELPERS ====================

    public function markCheckedIn(?int $operatorId = null): void
    {
        $this->update([
            'checked_in'    => true,
            'checked_in_at' => now(),
        ]);
    }

    public function markVoted(): void
    {
        $this->update([
            'has_voted' => true,
            'voted_at'  => now(),
        ]);
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function checkinLogs(): HasMany
    {
        return $this->hasMany(CheckinLog::class);
    }
}
