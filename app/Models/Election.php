<?php

namespace App\Models;

use App\Enums\ElectionStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Election extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'tahun_ajaran',
        'deskripsi',
        'start_at',
        'end_at',
        'status',
        'hasil_published_at',
        'created_by',
    ];

    protected $casts = [
        'start_at'           => 'datetime',
        'end_at'             => 'datetime',
        'hasil_published_at' => 'datetime',
        'status'             => ElectionStatus::class,
    ];

    // ==================== STATUS HELPERS ====================

    public function isDraft(): bool
    {
        return $this->status === ElectionStatus::DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === ElectionStatus::ACTIVE;
    }

    public function isClosed(): bool
    {
        return $this->status === ElectionStatus::CLOSED;
    }

    public function isPublished(): bool
    {
        return $this->status === ElectionStatus::PUBLISHED;
    }

    // ==================== TIME HELPERS ====================

    /**
     * Apakah waktu election sudah masuk (>= start_at)?
     */
    public function hasStarted(): bool
    {
        return now()->gte($this->start_at);
    }

    /**
     * Apakah waktu election sudah lewat (> end_at)?
     */
    public function hasEnded(): bool
    {
        return now()->gt($this->end_at);
    }

    /**
     * Apakah sekarang dalam rentang waktu election?
     */
    public function isWithinTimeWindow(): bool
    {
        return now()->between($this->start_at, $this->end_at);
    }

    /**
     * Apakah voting sedang dibuka?
     */
    public function isVotingOpen(): bool
    {
        return $this->isActive() && $this->isWithinTimeWindow();
    }

    /**
     * Status voting dalam bentuk string (untuk UI).
     */
    public function getVotingStatus(): string
    {
        if ($this->isDraft()) {
            return 'draft';
        }

        if ($this->isPublished()) {
            return 'published';
        }

        if ($this->isActive()) {
            if ($this->hasEnded()) {
                return 'expired';
            }

            if (!$this->hasStarted()) {
                return 'waiting';
            }

            return 'open';
        }

        return 'closed';
    }

    // ==================== RUNTIME STATUS (UI) ====================

    /**
     * ✅ Status runtime untuk badge UI.
     *
     * Return:
     * - 'draft'     → masih draft, belum waktunya
     * - 'ready'     → sudah waktunya (auto-aktif akan segera)
     * - 'active'    → sedang berlangsung
     * - 'expired'   → terlewat tanpa pernah aktif
     * - 'closed'    → sudah ditutup
     * - 'published' → sudah dipublikasi
     */
    public function getRuntimeStatus(): string
    {
        // Published
        if ($this->isPublished()) {
            return 'published';
        }

        // Closed
        if ($this->isClosed()) {
            return 'closed';
        }

        // Active
        if ($this->isActive()) {
            return 'active';
        }

        // Draft
        if ($this->isDraft()) {
            if ($this->hasEnded()) {
                return 'expired';
            }

            if ($this->hasStarted()) {
                return 'ready';
            }

            return 'draft';
        }

        return 'unknown';
    }

    /**
     * Label untuk badge UI.
     */
    public function getRuntimeLabel(): string
    {
        return match ($this->getRuntimeStatus()) {
            'draft'     => 'Draft',
            'ready'     => 'Siap Aktif',
            'active'    => 'Berlangsung',
            'expired'   => 'Terlewat',
            'closed'    => 'Ditutup',
            'published' => 'Dipublikasi',
            default     => '—',
        };
    }

    /**
     * Warna Bootstrap untuk badge.
     */
    public function getRuntimeColor(): string
    {
        return match ($this->getRuntimeStatus()) {
            'draft'     => 'secondary',
            'ready'     => 'warning',
            'active'    => 'success',
            'expired'   => 'danger',
            'closed'    => 'warning',
            'published' => 'primary',
            default     => 'secondary',
        };
    }

    // ==================== ACTIVATION ====================

    /**
     * ✅ AUTO-ACTIVATE: draft → active.
     *
     * Syarat:
     * - Status masih draft
     * - Waktu sudah masuk (start_at <= now() < end_at)
     * - Minimal 2 kandidat
     *
     * Return: true kalau berhasil diaktifkan, false kalau tidak memenuhi syarat.
     */
    public function autoActivateIfReady(): bool
    {
        if ($this->status !== ElectionStatus::DRAFT) {
            return false;
        }

        if (!$this->hasStarted() || $this->hasEnded()) {
            return false;
        }

        if ($this->candidates()->count() < 2) {
            return false;
        }

        $this->update(['status' => ElectionStatus::ACTIVE]);

        return true;
    }

    // ==================== AUTO-CLOSE ====================

    /**
     * Auto-close kalau waktu sudah lewat.
     * Handle status ACTIVE & DRAFT (draft yang terlewat).
     */
    public function autoCloseIfEnded(): bool
    {
        if (!in_array($this->status, [ElectionStatus::ACTIVE, ElectionStatus::DRAFT])) {
            return false;
        }

        if (!$this->hasEnded()) {
            return false;
        }

        $this->update(['status' => ElectionStatus::CLOSED]);

        return true;
    }

    // ==================== RELATIONS ====================

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class)->orderBy('no_urut');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ElectionSession::class);
    }

    public function voters(): HasMany
    {
        return $this->hasMany(Voter::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function votingDevices(): HasMany
    {
        return $this->hasMany(VotingDevice::class);
    }

    public function checkinDevices(): HasMany
    {
        return $this->hasMany(CheckinDevice::class);
    }
}
