<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElectionSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'class_id',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'status',
        'operator_id',
        'activated_at',
        'closed_at',
    ];

    protected $casts = [
        'tanggal'      => 'date',
        'activated_at' => 'datetime',
        'closed_at'    => 'datetime',
        'status'       => SessionStatus::class,
    ];

    // ==================== HELPERS ====================

    public function isActive(): bool
    {
        return $this->status === SessionStatus::ACTIVE;
    }

    public function isScheduled(): bool
    {
        return $this->status === SessionStatus::SCHEDULED;
    }

    public function isClosed(): bool
    {
        return $this->status === SessionStatus::CLOSED;
    }

    public function totalVoters(): int
    {
        return $this->voters()->count();
    }

    public function totalCheckedIn(): int
    {
        return $this->voters()->where('checked_in', true)->count();
    }

    public function totalVoted(): int
    {
        return $this->voters()->where('has_voted', true)->count();
    }

    // ==================== RELATIONS ====================

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function voters(): HasMany
    {
        return $this->hasMany(Voter::class, 'session_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'session_id');
    }

    public function votingDevices(): HasMany
    {
        return $this->hasMany(VotingDevice::class, 'session_id');
    }

    public function checkinDevices(): HasMany
    {
        return $this->hasMany(CheckinDevice::class, 'session_id');
    }

    public function checkinLogs(): HasMany
    {
        return $this->hasMany(CheckinLog::class, 'session_id');
    }

    // ==================== TIME HELPERS ====================

    /**
     * Waktu mulai lengkap (tanggal + waktu_mulai).
     */
    public function startDateTime(): Carbon
    {
        return Carbon::parse($this->tanggal->format('Y-m-d') . ' ' . $this->waktu_mulai);
    }

    /**
     * Waktu selesai lengkap (tanggal + waktu_selesai).
     */
    public function endDateTime(): Carbon
    {
        return Carbon::parse($this->tanggal->format('Y-m-d') . ' ' . $this->waktu_selesai);
    }

    /**
     * Apakah waktu sesi sudah masuk (>= waktu_mulai)?
     */
    public function hasStarted(): bool
    {
        return now()->gte($this->startDateTime());
    }

    /**
     * Apakah waktu sesi sudah lewat (> waktu_selesai)?
     */
    public function hasEnded(): bool
    {
        return now()->gt($this->endDateTime());
    }

    /**
     * Apakah sekarang dalam rentang waktu sesi?
     */
    public function isWithinTimeWindow(): bool
    {
        return now()->between($this->startDateTime(), $this->endDateTime());
    }

    // ==================== AUTO-ACTIVATE ====================

    /**
     * ✅ AUTO-ACTIVATE: scheduled → active.
     *
     * Syarat:
     * - Status masih scheduled
     * - Dalam range waktu (hasStarted && !hasEnded)
     * - Election harus active
     *
     * CATATAN:
     * - Sesi dapat berjalan PARALEL. Method ini TIDAK menutup sesi
     *   ACTIVE lain di election yang sama.
     * - Sesi hanya akan tertutup saat waktunya habis (autoCloseIfEnded)
     *   atau ditutup manual oleh admin.
     */
    public function autoActivateIfReady(): bool
    {
        if ($this->status !== SessionStatus::SCHEDULED) {
            return false;
        }

        if (!$this->hasStarted() || $this->hasEnded()) {
            return false;
        }

        // Cek parent election harus active
        if (!$this->election || !$this->election->isActive()) {
            return false;
        }

        $this->update([
            'status'       => SessionStatus::ACTIVE,
            'activated_at' => now(),
        ]);

        Cache::forget('operator.dashboard');
        Cache::forget('admin.live_stats');

        return true;
    }

    // ==================== AUTO-CLOSE ====================

    /**
     * Auto-close session ACTIVE yang waktunya sudah lewat.
     */
    public function autoCloseIfEnded(): bool
    {
        if ($this->status === SessionStatus::ACTIVE && $this->hasEnded()) {
            $this->update([
                'status'    => SessionStatus::CLOSED,
                'closed_at' => now(),
            ]);

            Cache::forget('operator.dashboard');
            Cache::forget('admin.live_stats');
            return true;
        }
        return false;
    }

    /**
     * Auto-close session SCHEDULED yang waktunya sudah lewat
     * (tanpa pernah diaktifkan).
     */
    public function autoCloseIfMissed(): bool
    {
        if ($this->status === SessionStatus::SCHEDULED && $this->hasEnded()) {
            $this->update([
                'status'    => SessionStatus::CLOSED,
                'closed_at' => now(),
            ]);
            return true;
        }
        return false;
    }

    /**
     * Gabungan: auto-close baik active maupun scheduled yang lewat.
     */
    public function autoCloseAny(): bool
    {
        return $this->autoCloseIfEnded() || $this->autoCloseIfMissed();
    }

    // ==================== RUNTIME STATUS (untuk UI) ====================

    /**
     * Status dinamis berdasarkan waktu.
     *
     * Return:
     * - 'scheduled'  → belum waktunya
     * - 'ready'      → sudah masuk waktu, siap diaktifkan (auto)
     * - 'active'     → sedang berlangsung
     * - 'expired'    → terlewat (belum/tidak diaktifkan)
     * - 'closed'     → sudah ditutup
     */
    public function getRuntimeStatus(): string
    {
        // Sudah closed
        if ($this->status === SessionStatus::CLOSED) {
            return 'closed';
        }

        // Masih scheduled
        if ($this->status === SessionStatus::SCHEDULED) {
            if ($this->hasEnded()) {
                return 'expired';
            }

            if ($this->hasStarted()) {
                return 'ready';
            }

            return 'scheduled';
        }

        // Active
        if ($this->status === SessionStatus::ACTIVE) {
            if ($this->hasEnded()) {
                return 'expired';
            }

            return 'active';
        }

        return 'unknown';
    }

    /**
     * Label untuk badge UI.
     */
    public function getRuntimeLabel(): string
    {
        return match ($this->getRuntimeStatus()) {
            'scheduled' => 'Terjadwal',
            'ready'     => 'Siap Aktif',
            'active'    => 'Sedang Berlangsung',
            'expired'   => 'Terlewat',
            'closed'    => 'Selesai',
            default     => '—',
        };
    }

    /**
     * Warna badge UI (Bootstrap).
     */
    public function getRuntimeColor(): string
    {
        return match ($this->getRuntimeStatus()) {
            'scheduled' => 'secondary',
            'ready'     => 'warning',
            'active'    => 'success',
            'expired'   => 'danger',
            'closed'    => 'dark',
            default     => 'secondary',
        };
    }
}
