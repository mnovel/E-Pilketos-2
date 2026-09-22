<?php

namespace App\Models;

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

    // ==================== RELATIONS ====================

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verifiedUsers(): HasMany
    {
        return $this->hasMany(User::class, 'verified_by');
    }

    public function voter(): HasOne
    {
        return $this->hasOne(Voter::class);
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
