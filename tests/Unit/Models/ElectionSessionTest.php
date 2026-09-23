<?php

namespace Tests\Unit\Models;

use App\Enums\SessionStatus;
use App\Models\ElectionSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectionSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_date_time_combines_date_and_time()
    {
        $session = new ElectionSession([
            'tanggal'     => '2026-09-23',
            'waktu_mulai' => '14:30',
        ]);

        $this->assertEquals('2026-09-23 14:30:00', $session->startDateTime()->format('Y-m-d H:i:s'));
    }

    public function test_end_date_time_combines_date_and_time()
    {
        $session = new ElectionSession([
            'tanggal'       => '2026-09-23',
            'waktu_selesai' => '15:30',
        ]);

        $this->assertEquals('2026-09-23 15:30:00', $session->endDateTime()->format('Y-m-d H:i:s'));
    }

    public function test_is_within_time_window_returns_true_when_now_between_times()
    {
        $session = new ElectionSession([
            'tanggal'       => now()->format('Y-m-d'),
            'waktu_mulai'   => now()->subMinutes(5)->format('H:i'),
            'waktu_selesai' => now()->addMinutes(5)->format('H:i'),
        ]);

        $this->assertTrue($session->isWithinTimeWindow());
    }

    public function test_is_within_time_window_returns_false_when_before_start()
    {
        // ✅ Pakai BESOK untuk menghindari rollover jam
        $session = new ElectionSession([
            'tanggal'       => now()->addDay()->format('Y-m-d'),
            'waktu_mulai'   => '10:00',
            'waktu_selesai' => '11:00',
        ]);

        $this->assertFalse($session->isWithinTimeWindow());
    }

    public function test_is_within_time_window_returns_false_when_after_end()
    {
        // ✅ Pakai KEMARIN untuk menghindari rollover jam
        $session = new ElectionSession([
            'tanggal'       => now()->subDay()->format('Y-m-d'),
            'waktu_mulai'   => '10:00',
            'waktu_selesai' => '11:00',
        ]);

        $this->assertFalse($session->isWithinTimeWindow());
    }

    public function test_is_active_returns_true_only_for_active_status()
    {
        $active = new ElectionSession(['status' => SessionStatus::ACTIVE]);
        $scheduled = new ElectionSession(['status' => SessionStatus::SCHEDULED]);
        $closed = new ElectionSession(['status' => SessionStatus::CLOSED]);

        $this->assertTrue($active->isActive());
        $this->assertFalse($scheduled->isActive());
        $this->assertFalse($closed->isActive());
    }

    public function test_is_scheduled_returns_true_only_for_scheduled_status()
    {
        $scheduled = new ElectionSession(['status' => SessionStatus::SCHEDULED]);
        $active = new ElectionSession(['status' => SessionStatus::ACTIVE]);

        $this->assertTrue($scheduled->isScheduled());
        $this->assertFalse($active->isScheduled());
    }

    public function test_is_closed_returns_true_only_for_closed_status()
    {
        $closed = new ElectionSession(['status' => SessionStatus::CLOSED]);
        $active = new ElectionSession(['status' => SessionStatus::ACTIVE]);

        $this->assertTrue($closed->isClosed());
        $this->assertFalse($active->isClosed());
    }
}
