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
        // ✅ Cache 60 detik — home page paling sering diakses
        $activeElection = Cache::remember('home.active_election', 60, function () {
            // Prioritas 1: election yang SEDANG aktif
            $active = Election::where('status', ElectionStatus::ACTIVE)
                ->where('start_at', '<=', now())
                ->where('end_at', '>', now())
                ->with(['candidates' => fn($q) => $q->orderBy('no_urut')])
                ->first();

            if ($active) {
                return $active;
            }

            // Prioritas 2: election yang akan datang / draft
            return Election::whereIn('status', [
                ElectionStatus::DRAFT,
                ElectionStatus::ACTIVE,
            ])
                ->orderBy('start_at')
                ->with(['candidates' => fn($q) => $q->orderBy('no_urut')])
                ->first();
        });

        // ✅ Cache 5 menit — published election jarang berubah
        $publishedElection = Cache::remember('home.published_election', 300, function () {
            return Election::where('status', ElectionStatus::PUBLISHED)
                ->latest('hasil_published_at')
                ->first();
        });

        return view('home', compact('activeElection', 'publishedElection'));
    }
}
