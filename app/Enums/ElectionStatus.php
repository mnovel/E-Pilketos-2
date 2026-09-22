<?php

namespace App\Enums;

enum ElectionStatus: string
{
    case DRAFT     = 'draft';
    case ACTIVE    = 'active';
    case CLOSED    = 'closed';
    case PUBLISHED = 'published';

    // ==========================================
    // LABELS
    // ==========================================

    /**
     * Label Bahasa Indonesia untuk UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Draft',
            self::ACTIVE    => 'Berlangsung',
            self::CLOSED    => 'Ditutup',
            self::PUBLISHED => 'Dipublikasi',
        };
    }

    /**
     * Warna Bootstrap badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT     => 'secondary',
            self::ACTIVE    => 'success',
            self::CLOSED    => 'warning',
            self::PUBLISHED => 'primary',
        };
    }

    /**
     * Icon Bootstrap Icons.
     */
    public function icon(): string
    {
        return match ($this) {
            self::DRAFT     => 'bi-file-earmark',
            self::ACTIVE    => 'bi-broadcast',
            self::CLOSED    => 'bi-stop-circle',
            self::PUBLISHED => 'bi-megaphone-fill',
        };
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isClosed(): bool
    {
        return $this === self::CLOSED;
    }

    public function isPublished(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Election masih bisa diedit (draft only).
     */
    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Election sedang berjalan (untuk voting).
     */
    public function isVoting(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Election sudah final (closed/published).
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::CLOSED, self::PUBLISHED], true);
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
