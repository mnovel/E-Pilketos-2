<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'meta',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    // ==================== RELATIONS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== HELPERS ====================

    /**
     * Helper untuk log activity.
     */
    public static function log(
        string $action,
        array $data = [],
        ?int $userId = null
    ): self {
        return self::create([
            'user_id'      => $userId ?? auth()->id(),
            'action'       => $action,
            'subject_type' => $data['subject_type'] ?? null,
            'subject_id'   => $data['subject_id'] ?? null,
            'meta'         => $data['meta'] ?? null,
            'ip_address'   => request()?->ip(),
            'user_agent'   => request()?->userAgent(),
        ]);
    }

    /**
     * Label untuk action.
     */
    public function getActionLabel(): string
    {
        return match ($this->action) {
            'auth.login'             => 'Login',
            'auth.logout'            => 'Logout',
            'voter.register'         => 'Daftar Voter',
            'voter.verified'         => 'Verifikasi Voter',
            'voter.rejected'         => 'Tolak Voter',
            'voter.bulk_verified'    => 'Verifikasi Voter (Bulk)',
            'voter.bulk_rejected'    => 'Tolak Voter (Bulk)',
            'voter.imported'         => 'Import Voter',
            'election.created'       => 'Buat Pemilihan',
            'election.updated'       => 'Edit Pemilihan',
            'election.deleted'       => 'Hapus Pemilihan',
            'election.activated'     => 'Aktifkan Pemilihan',
            'election.closed'        => 'Tutup Pemilihan',
            'election.published'     => 'Publikasi Hasil',
            'candidate.created'      => 'Tambah Kandidat',
            'candidate.updated'      => 'Edit Kandidat',
            'candidate.deleted'      => 'Hapus Kandidat',
            'session.created'        => 'Buat Sesi',
            'session.updated'        => 'Edit Sesi',
            'session.deleted'        => 'Hapus Sesi',
            'session.activated'      => 'Aktifkan Sesi',
            'session.closed'         => 'Tutup Sesi',
            'session.assigned'       => 'Assign Pemilih',
            'class.created'          => 'Tambah Kelas',
            'class.updated'          => 'Edit Kelas',
            'class.deleted'          => 'Hapus Kelas',
            'class.toggled'          => 'Toggle Kelas',
            'operator.created'       => 'Tambah Operator',
            'operator.updated'       => 'Edit Operator',
            'operator.deleted'       => 'Hapus Operator',
            'operator.password_reset' => 'Reset Password Operator',
            'vote.submitted'         => 'Vote',
            'checkin.success'        => 'Check-in',
            'profile.updated'        => 'Update Profile',
            'profile.password'       => 'Ubah Password',
            default                  => $this->action,
        };
    }

    /**
     * Icon untuk action.
     */
    public function getActionIcon(): string
    {
        return match (true) {
            str_starts_with($this->action, 'auth.')      => 'bi-box-arrow-in-right',
            str_starts_with($this->action, 'voter.')     => 'bi-people',
            str_starts_with($this->action, 'election.')  => 'bi-calendar-event',
            str_starts_with($this->action, 'candidate.') => 'bi-person-badge',
            str_starts_with($this->action, 'session.')   => 'bi-clock-history',
            str_starts_with($this->action, 'class.')     => 'bi-mortarboard',
            str_starts_with($this->action, 'operator.')  => 'bi-person-badge-fill',
            str_starts_with($this->action, 'vote.')      => 'bi-check2-square',
            str_starts_with($this->action, 'checkin.')   => 'bi-door-open',
            str_starts_with($this->action, 'profile.')   => 'bi-person',
            default                                       => 'bi-info-circle',
        };
    }

    /**
     * Warna untuk action.
     */
    public function getActionColor(): string
    {
        return match (true) {
            str_contains($this->action, 'deleted')  => 'danger',
            str_contains($this->action, 'rejected') => 'danger',
            str_contains($this->action, 'created')  => 'success',
            str_contains($this->action, 'verified') => 'success',
            str_contains($this->action, 'updated')  => 'warning',
            str_contains($this->action, 'login')    => 'info',
            str_contains($this->action, 'logout')   => 'secondary',
            default                                  => 'primary',
        };
    }
}
