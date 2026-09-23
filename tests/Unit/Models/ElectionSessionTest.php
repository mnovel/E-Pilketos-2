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
        $session = new ElectionSession([
            'tanggal'       => now()->format('Y-m-d'),
            'waktu_mulai'   => now()->addHours(2)->format('H:i'),
            'waktu_selesai' => now()->addHours(3)->format('H:i'),
        ]);

        $this->assertFalse($session->isWithinTimeWindow());
    }
}
