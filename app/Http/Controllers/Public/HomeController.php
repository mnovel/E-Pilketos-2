<?php

namespace App\Http\Controllers\Public;

use App\Enums\ElectionStatus;
use App\Http\Controllers\Controller;
use App\Models\Election;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Landing page.
     */
    public function index(): View
    {
        // ✅ Cache hanya ID (bukan model) — hindari error unserialize Eloquent
        $activeElectionId = Cache::remember('home.active_election_id', 60, function () {
            // Prioritas 1: election yang SEDANG aktif
            $id = Election::where('status', ElectionStatus::ACTIVE)
                ->where('start_at', '<=', now())
                ->where('end_at', '>', now())
                ->value('id');

            if ($id) {
                return $id;
            }

            // Prioritas 2: election yang akan datang / draft
            return Election::whereIn('status', [
                ElectionStatus::DRAFT,
                ElectionStatus::ACTIVE,
            ])
                ->orderBy('start_at')
                ->value('id');
        });

        // Query fresh dengan relasi (tidak di-cache supaya aman)
        $activeElection = $activeElectionId
            ? Election::with([
                'candidates' => fn($q) => $q->orderBy('no_urut')->with('classRoom'),
            ])->find($activeElectionId)
            : null;

        // ✅ Sama untuk published election
        $publishedElectionId = Cache::remember('home.published_election_id', 300, function () {
            return Election::where('status', ElectionStatus::PUBLISHED)
                ->latest('hasil_published_at')
                ->value('id');
        });

        $publishedElection = $publishedElectionId
            ? Election::find($publishedElectionId)
            : null;

        return view('home', compact('activeElection', 'publishedElection'));
    }
}
