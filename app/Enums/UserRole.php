<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN    = 'admin';
    case OPERATOR = 'operator';
    case VOTER    = 'voter';

    // ==========================================
    // LABELS
    // ==========================================

    public function label(): string
    {
        return match ($this) {
            self::ADMIN    => 'Administrator',
            self::OPERATOR => 'Operator',
            self::VOTER    => 'Pemilih',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ADMIN    => 'primary',
            self::OPERATOR => 'info',
            self::VOTER    => 'success',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ADMIN    => 'bi-shield-check',
            self::OPERATOR => 'bi-person-badge',
            self::VOTER    => 'bi-person',
        };
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public function isOperator(): bool
    {
        return $this === self::OPERATOR;
    }

    public function isVoter(): bool
    {
        return $this === self::VOTER;
    }

    /**
     * Role yang punya akses ke device (check-in/voting).
     */
    public function canOperateDevice(): bool
    {
        return in_array($this, [self::ADMIN, self::OPERATOR], true);
    }

    /**
     * Role yang butuh verifikasi (voter only).
     */
    public function requiresVerification(): bool
    {
        return $this === self::VOTER;
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
