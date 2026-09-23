<?php

namespace Tests\Unit\Enums;

use App\Enums\DeviceStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DeviceStatusTest extends TestCase
{
    // ==========================================
    // LABEL
    // ==========================================

    #[DataProvider('labelProvider')]
    public function test_label_returns_correct_string(DeviceStatus $status, string $expectedLabel)
    {
        $this->assertEquals($expectedLabel, $status->label());
    }

    public static function labelProvider(): array
    {
        return [
            'idle'     => [DeviceStatus::IDLE,     'Menunggu'],
            'assigned' => [DeviceStatus::ASSIGNED, 'Terisi'],
            'voting'   => [DeviceStatus::VOTING,   'Voting'],
            'done'     => [DeviceStatus::DONE,     'Selesai'],
        ];
    }

    // ==========================================
    // COLOR
    // ==========================================

    #[DataProvider('colorProvider')]
    public function test_color_returns_correct_bootstrap_class(DeviceStatus $status, string $expectedColor)
    {
        $this->assertEquals($expectedColor, $status->color());
    }

    public static function colorProvider(): array
    {
        return [
            'idle'     => [DeviceStatus::IDLE,     'secondary'],
            'assigned' => [DeviceStatus::ASSIGNED, 'warning'],
            'voting'   => [DeviceStatus::VOTING,   'success'],
            'done'     => [DeviceStatus::DONE,     'primary'],
        ];
    }

    // ==========================================
    // ICON
    // ==========================================

    #[DataProvider('iconProvider')]
    public function test_icon_returns_correct_bootstrap_icon(DeviceStatus $status, string $expectedIcon)
    {
        $this->assertEquals($expectedIcon, $status->icon());
    }

    public static function iconProvider(): array
    {
        return [
            'idle'     => [DeviceStatus::IDLE,     'bi-hourglass'],
            'assigned' => [DeviceStatus::ASSIGNED, 'bi-person-check'],
            'voting'   => [DeviceStatus::VOTING,   'bi-check2-square'],
            'done'     => [DeviceStatus::DONE,     'bi-check-circle-fill'],
        ];
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function test_is_idle_returns_true_only_for_idle()
    {
        $this->assertTrue(DeviceStatus::IDLE->isIdle());
        $this->assertFalse(DeviceStatus::ASSIGNED->isIdle());
        $this->assertFalse(DeviceStatus::VOTING->isIdle());
        $this->assertFalse(DeviceStatus::DONE->isIdle());
    }

    public function test_is_busy_returns_true_for_assigned_and_voting()
    {
        $this->assertFalse(DeviceStatus::IDLE->isBusy());
        $this->assertTrue(DeviceStatus::ASSIGNED->isBusy());
        $this->assertTrue(DeviceStatus::VOTING->isBusy());
        $this->assertFalse(DeviceStatus::DONE->isBusy());
    }

    public function test_is_finished_returns_true_only_for_done()
    {
        $this->assertFalse(DeviceStatus::IDLE->isFinished());
        $this->assertFalse(DeviceStatus::ASSIGNED->isFinished());
        $this->assertFalse(DeviceStatus::VOTING->isFinished());
        $this->assertTrue(DeviceStatus::DONE->isFinished());
    }

    // ==========================================
    // STATIC
    // ==========================================

    public function test_values_returns_all_case_values()
    {
        $values = DeviceStatus::values();

        $this->assertIsArray($values);
        $this->assertContains('idle', $values);
        $this->assertContains('assigned', $values);
        $this->assertContains('voting', $values);
        $this->assertContains('done', $values);
        $this->assertCount(4, $values);
    }

    public function test_options_returns_value_to_label_mapping()
    {
        $options = DeviceStatus::options();

        $this->assertEquals('Menunggu', $options['idle']);
        $this->assertEquals('Terisi', $options['assigned']);
        $this->assertEquals('Voting', $options['voting']);
        $this->assertEquals('Selesai', $options['done']);
    }
}
