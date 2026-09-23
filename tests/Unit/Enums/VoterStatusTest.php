<?php

namespace Tests\Unit\Enums;

use App\Enums\VoterStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VoterStatusTest extends TestCase
{
    // ==========================================
    // LABEL
    // ==========================================

    #[DataProvider('labelProvider')]
    public function test_label_returns_correct_string(VoterStatus $status, string $expectedLabel)
    {
        $this->assertEquals($expectedLabel, $status->label());
    }

    public static function labelProvider(): array
    {
        return [
            'pending'  => [VoterStatus::PENDING,  'Menunggu Verifikasi'],
            'verified' => [VoterStatus::VERIFIED, 'Terverifikasi'],
            'rejected' => [VoterStatus::REJECTED, 'Ditolak'],
        ];
    }

    // ==========================================
    // SHORT LABEL
    // ==========================================

    #[DataProvider('shortLabelProvider')]
    public function test_short_label_returns_correct_string(VoterStatus $status, string $expectedShortLabel)
    {
        $this->assertEquals($expectedShortLabel, $status->shortLabel());
    }

    public static function shortLabelProvider(): array
    {
        return [
            'pending'  => [VoterStatus::PENDING,  'Menunggu'],
            'verified' => [VoterStatus::VERIFIED, 'Terverifikasi'],
            'rejected' => [VoterStatus::REJECTED, 'Ditolak'],
        ];
    }

    // ==========================================
    // COLOR
    // ==========================================

    #[DataProvider('colorProvider')]
    public function test_color_returns_correct_bootstrap_class(VoterStatus $status, string $expectedColor)
    {
        $this->assertEquals($expectedColor, $status->color());
    }

    public static function colorProvider(): array
    {
        return [
            'pending'  => [VoterStatus::PENDING,  'warning'],
            'verified' => [VoterStatus::VERIFIED, 'success'],
            'rejected' => [VoterStatus::REJECTED, 'danger'],
        ];
    }

    // ==========================================
    // ICON
    // ==========================================

    #[DataProvider('iconProvider')]
    public function test_icon_returns_correct_bootstrap_icon(VoterStatus $status, string $expectedIcon)
    {
        $this->assertEquals($expectedIcon, $status->icon());
    }

    public static function iconProvider(): array
    {
        return [
            'pending'  => [VoterStatus::PENDING,  'bi-hourglass-split'],
            'verified' => [VoterStatus::VERIFIED, 'bi-check-circle-fill'],
            'rejected' => [VoterStatus::REJECTED, 'bi-x-circle-fill'],
        ];
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function test_is_pending_returns_true_only_for_pending()
    {
        $this->assertTrue(VoterStatus::PENDING->isPending());
        $this->assertFalse(VoterStatus::VERIFIED->isPending());
        $this->assertFalse(VoterStatus::REJECTED->isPending());
    }

    public function test_is_verified_returns_true_only_for_verified()
    {
        $this->assertFalse(VoterStatus::PENDING->isVerified());
        $this->assertTrue(VoterStatus::VERIFIED->isVerified());
        $this->assertFalse(VoterStatus::REJECTED->isVerified());
    }

    public function test_is_rejected_returns_true_only_for_rejected()
    {
        $this->assertFalse(VoterStatus::PENDING->isRejected());
        $this->assertFalse(VoterStatus::VERIFIED->isRejected());
        $this->assertTrue(VoterStatus::REJECTED->isRejected());
    }

    public function test_can_login_returns_true_only_for_verified()
    {
        $this->assertFalse(VoterStatus::PENDING->canLogin());
        $this->assertTrue(VoterStatus::VERIFIED->canLogin());
        $this->assertFalse(VoterStatus::REJECTED->canLogin());
    }

    public function test_is_actionable_returns_true_for_pending_and_rejected()
    {
        $this->assertTrue(VoterStatus::PENDING->isActionable());
        $this->assertFalse(VoterStatus::VERIFIED->isActionable());
        $this->assertTrue(VoterStatus::REJECTED->isActionable());
    }

    public function test_is_final_returns_true_only_for_verified()
    {
        $this->assertFalse(VoterStatus::PENDING->isFinal());
        $this->assertTrue(VoterStatus::VERIFIED->isFinal());
        $this->assertFalse(VoterStatus::REJECTED->isFinal());
    }

    // ==========================================
    // STATIC
    // ==========================================

    public function test_values_returns_all_case_values()
    {
        $values = VoterStatus::values();

        $this->assertIsArray($values);
        $this->assertContains('pending', $values);
        $this->assertContains('verified', $values);
        $this->assertContains('rejected', $values);
        $this->assertCount(3, $values);
    }

    public function test_options_returns_value_to_label_mapping()
    {
        $options = VoterStatus::options();

        $this->assertEquals('Menunggu Verifikasi', $options['pending']);
        $this->assertEquals('Terverifikasi', $options['verified']);
        $this->assertEquals('Ditolak', $options['rejected']);
    }
}
