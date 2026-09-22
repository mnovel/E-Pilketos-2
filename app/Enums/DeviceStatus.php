<?php

namespace App\Enums;

enum DeviceStatus: string
{
    case IDLE     = 'idle';
    case ASSIGNED = 'assigned';
    case VOTING   = 'voting';
    case DONE     = 'done';

    // ==========================================
    // LABELS
    // ==========================================

    /**
     * Label Bahasa Indonesia untuk UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::IDLE     => 'Menunggu',
            self::ASSIGNED => 'Terisi',
            self::VOTING   => 'Voting',
            self::DONE     => 'Selesai',
        };
    }

    /**
     * Warna Bootstrap badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::IDLE     => 'secondary',
            self::ASSIGNED => 'warning',
            self::VOTING   => 'success',
            self::DONE     => 'primary',
        };
    }

    /**
     * Icon Bootstrap Icons.
     */
    public function icon(): string
    {
        return match ($this) {
            self::IDLE     => 'bi-hourglass',
            self::ASSIGNED => 'bi-person-check',
            self::VOTING   => 'bi-check2-square',
            self::DONE     => 'bi-check-circle-fill',
        };
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Device sedang menunggu (bisa di-assign).
     */
    public function isIdle(): bool
    {
        return $this === self::IDLE;
    }

    /**
     * Device sedang digunakan voter.
     */
    public function isBusy(): bool
    {
        return in_array($this, [self::ASSIGNED, self::VOTING], true);
    }

    /**
     * Device sudah selesai (bisa di-reset).
     */
    public function isFinished(): bool
    {
        return $this === self::DONE;
    }

    // ==========================================
    // STATIC
    // ==========================================

    /**
     * List semua value untuk validasi / dropdown.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * List untuk dropdown: [value => label].
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
