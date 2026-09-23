<?php

namespace Tests\Unit\Enums;

use App\Enums\SessionStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SessionStatusTest extends TestCase
{
    // ==========================================
    // LABEL
    // ==========================================

    #[DataProvider('labelProvider')]
    public function test_label_returns_correct_string(SessionStatus $status, string $expectedLabel)
    {
        $this->assertEquals($expectedLabel, $status->label());
    }

    public static function labelProvider(): array
    {
        return [
            'scheduled' => [SessionStatus::SCHEDULED, 'Terjadwal'],
            'active'    => [SessionStatus::ACTIVE,    'Sedang Berjalan'],
            'closed'    => [SessionStatus::CLOSED,    'Selesai'],
        ];
    }

    // ==========================================
    // COLOR
    // ==========================================

    #[DataProvider('colorProvider')]
    public function test_color_returns_correct_bootstrap_class(SessionStatus $status, string $expectedColor)
    {
        $this->assertEquals($expectedColor, $status->color());
    }

    public static function colorProvider(): array
    {
        return [
            'scheduled' => [SessionStatus::SCHEDULED, 'secondary'],
            'active'    => [SessionStatus::ACTIVE,    'success'],
            'closed'    => [SessionStatus::CLOSED,    'warning'],
        ];
    }

    // ==========================================
    // ICON
    // ==========================================

    #[DataProvider('iconProvider')]
    public function test_icon_returns_correct_bootstrap_icon(SessionStatus $status, string $expectedIcon)
    {
        $this->assertEquals($expectedIcon, $status->icon());
    }

    public static function iconProvider(): array
    {
        return [
            'scheduled' => [SessionStatus::SCHEDULED, 'bi-clock'],
            'active'    => [SessionStatus::ACTIVE,    'bi-broadcast'],
            'closed'    => [SessionStatus::CLOSED,    'bi-stop-circle-fill'],
        ];
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function test_is_scheduled_returns_true_only_for_scheduled()
    {
        $this->assertTrue(SessionStatus::SCHEDULED->isScheduled());
        $this->assertFalse(SessionStatus::ACTIVE->isScheduled());
        $this->assertFalse(SessionStatus::CLOSED->isScheduled());
    }

    public function test_is_active_returns_true_only_for_active()
    {
        $this->assertFalse(SessionStatus::SCHEDULED->isActive());
        $this->assertTrue(SessionStatus::ACTIVE->isActive());
        $this->assertFalse(SessionStatus::CLOSED->isActive());
    }

    public function test_is_closed_returns_true_only_for_closed()
    {
        $this->assertFalse(SessionStatus::SCHEDULED->isClosed());
        $this->assertFalse(SessionStatus::ACTIVE->isClosed());
        $this->assertTrue(SessionStatus::CLOSED->isClosed());
    }

    public function test_is_editable_returns_true_only_for_scheduled()
    {
        $this->assertTrue(SessionStatus::SCHEDULED->isEditable());
        $this->assertFalse(SessionStatus::ACTIVE->isEditable());
        $this->assertFalse(SessionStatus::CLOSED->isEditable());
    }

    public function test_is_voting_returns_true_only_for_active()
    {
        $this->assertFalse(SessionStatus::SCHEDULED->isVoting());
        $this->assertTrue(SessionStatus::ACTIVE->isVoting());
        $this->assertFalse(SessionStatus::CLOSED->isVoting());
    }

    public function test_is_final_returns_true_only_for_closed()
    {
        $this->assertFalse(SessionStatus::SCHEDULED->isFinal());
        $this->assertFalse(SessionStatus::ACTIVE->isFinal());
        $this->assertTrue(SessionStatus::CLOSED->isFinal());
    }

    // ==========================================
    // STATIC
    // ==========================================

    public function test_values_returns_all_case_values()
    {
        $values = SessionStatus::values();

        $this->assertIsArray($values);
        $this->assertContains('scheduled', $values);
        $this->assertContains('active', $values);
        $this->assertContains('closed', $values);
        $this->assertCount(3, $values);
    }

    public function test_options_returns_value_to_label_mapping()
    {
        $options = SessionStatus::options();

        $this->assertEquals('Terjadwal', $options['scheduled']);
        $this->assertEquals('Sedang Berjalan', $options['active']);
        $this->assertEquals('Selesai', $options['closed']);
    }
}
