<?php

namespace App\Models;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Enums\VoterStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'nis',
        'name',
        'class_id',
        'email',
        'password',
        'role',
        'status',
        'kartu_pelajar',
        'verified_by',
        'verified_at',
        'alasan_reject',
        'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'verified_at'       => 'datetime',
        'last_login_at'     => 'datetime',
        'password'          => 'hashed',
        'role'              => UserRole::class,
        'status'            => VoterStatus::class,
    ];

    // ==================== HELPERS ====================

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isOperator(): bool
    {
        return $this->role === UserRole::OPERATOR;
    }

    public function isVoter(): bool
    {
        return $this->role === UserRole::VOTER;
    }

    public function isVerified(): bool
    {
        return $this->status === VoterStatus::VERIFIED;
    }

    public function isPending(): bool
    {
        return $this->status === VoterStatus::PENDING;
    }

    // ==================== VOTER LOCK HELPERS ====================

    /**
     * Cek apakah data voter ini boleh diedit admin.
     *
     * Tidak boleh edit kalau salah satu dari voterRecords:
     * - Terdaftar di election ACTIVE
     * - Terdaftar di session ACTIVE
     * - Sudah check-in
     * - Sudah vote
     */
    public function canEditVoterData(): bool
    {
        // Bukan voter → bebas
        if (!$this->isVoter()) {
            return true;
        }

        foreach ($this->voterRecords as $record) {
            if ($record->election && $record->election->status === ElectionStatus::ACTIVE) {
                return false;
            }

            if ($record->session && $record->session->status === SessionStatus::ACTIVE) {
                return false;
            }

            if ($record->checked_in || $record->has_voted) {
                return false;
            }
        }

        return true;
    }

    /**
     * Alasan kenapa voter tidak bisa diedit (untuk tooltip / alert).
     * Return null kalau bisa edit.
     */
    public function getEditLockReason(): ?string
    {
        if (!$this->isVoter()) {
            return null;
        }

        foreach ($this->voterRecords as $record) {
            if ($record->election && $record->election->status === ElectionStatus::ACTIVE) {
                return 'Pemilihan sedang aktif';
            }

            if ($record->session && $record->session->status === SessionStatus::ACTIVE) {
                return 'Sesi sedang aktif';
            }

            if ($record->has_voted) {
                return 'Voter sudah melakukan voting';
            }

            if ($record->checked_in) {
                return 'Voter sudah check-in';
            }
        }

        return null;
    }

    // ==================== RELATIONS ====================

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verifiedUsers(): HasMany
    {
        return $this->hasMany(User::class, 'verified_by');
    }

    /**
     * Relasi lama (hasOne) — dibiarkan untuk backward compat.
     */
    public function voter(): HasOne
    {
        return $this->hasOne(Voter::class);
    }

    /**
     * Semua record voter (multi-election).
     * Dipakai untuk cek lock (election active / session active / vote / check-in).
     */
    public function voterRecords(): HasMany
    {
        return $this->hasMany(Voter::class);
    }

    public function operatedSessions(): HasMany
    {
        return $this->hasMany(ElectionSession::class, 'operator_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
