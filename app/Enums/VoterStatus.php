<?php

namespace App\Enums;

enum VoterStatus: string
{
    case PENDING  = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    // ==========================================
    // LABELS
    // ==========================================

    public function label(): string
    {
        return match ($this) {
            self::PENDING  => 'Menunggu Verifikasi',
            self::VERIFIED => 'Terverifikasi',
            self::REJECTED => 'Ditolak',
        };
    }

    /**
     * Label pendek (untuk badge kecil).
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::PENDING  => 'Menunggu',
            self::VERIFIED => 'Terverifikasi',
            self::REJECTED => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING  => 'warning',
            self::VERIFIED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING  => 'bi-hourglass-split',
            self::VERIFIED => 'bi-check-circle-fill',
            self::REJECTED => 'bi-x-circle-fill',
        };
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isVerified(): bool
    {
        return $this === self::VERIFIED;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    /**
     * Voter bisa login & voting.
     */
    public function canLogin(): bool
    {
        return $this === self::VERIFIED;
    }

    /**
     * Voter masih bisa di-edit / diverifikasi ulang.
     */
    public function isActionable(): bool
    {
        return $this !== self::VERIFIED;
    }

    /**
     * Status final (tidak bisa diubah lagi).
     */
    public function isFinal(): bool
    {
        return $this === self::VERIFIED;
    }

    // ==========================================
    // STATIC
    // ==========================================

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
