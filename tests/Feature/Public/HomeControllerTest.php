<?php

namespace Tests\Feature\Public;

use App\Enums\ElectionStatus;
use App\Models\Election;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads_successfully()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertViewIs('home');
    }

    public function test_home_shows_active_election_if_exists()
    {
        $activeElection = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHour(),
            'title'    => 'Pemilihan Aktif',
        ]);

        $response = $this->get('/');

        $response->assertViewHas('activeElection', function ($e) use ($activeElection) {
            return $e && $e->id === $activeElection->id;
        });
    }

    public function test_home_shows_latest_published_election()
    {
        Election::factory()->create([
            'status'             => ElectionStatus::PUBLISHED,
            'hasil_published_at' => now()->subDays(2),
        ]);

        $latestPublished = Election::factory()->create([
            'status'             => ElectionStatus::PUBLISHED,
            'hasil_published_at' => now()->subDay(),
        ]);

        $response = $this->get('/');

        $response->assertViewHas('publishedElection', function ($e) use ($latestPublished) {
            return $e && $e->id === $latestPublished->id;
        });
    }

    public function test_home_shows_draft_election_if_no_active()
    {
        $draft = Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->addHour(),
        ]);

        $response = $this->get('/');

        $response->assertViewHas('activeElection', function ($e) use ($draft) {
            return $e && $e->id === $draft->id;
        });
    }

    public function test_home_does_not_show_published_election_as_active()
    {
        Election::factory()->create([
            'status'             => ElectionStatus::PUBLISHED,
            'hasil_published_at' => now(),
        ]);

        $response = $this->get('/');

        $response->assertViewHas('activeElection', fn($e) => $e === null);
    }
}
