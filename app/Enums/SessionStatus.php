<?php

namespace App\Enums;

enum SessionStatus: string
{
    case SCHEDULED = 'scheduled';
    case ACTIVE    = 'active';
    case CLOSED    = 'closed';

    // ==========================================
    // LABELS
    // ==========================================

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Terjadwal',
            self::ACTIVE    => 'Sedang Berjalan',
            self::CLOSED    => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SCHEDULED => 'secondary',
            self::ACTIVE    => 'success',
            self::CLOSED    => 'warning',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SCHEDULED => 'bi-clock',
            self::ACTIVE    => 'bi-broadcast',
            self::CLOSED    => 'bi-stop-circle-fill',
        };
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function isScheduled(): bool
    {
        return $this === self::SCHEDULED;
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isClosed(): bool
    {
        return $this === self::CLOSED;
    }

    /**
     * Session masih bisa diedit (scheduled only).
     */
    public function isEditable(): bool
    {
        return $this === self::SCHEDULED;
    }

    /**
     * Session sedang berjalan (voting aktif).
     */
    public function isVoting(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Session sudah final.
     */
    public function isFinal(): bool
    {
        return $this === self::CLOSED;
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
