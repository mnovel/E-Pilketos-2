<?php

namespace App\Http\Controllers\Public;

use App\Enums\ElectionStatus;
use App\Http\Controllers\Controller;
use App\Models\Election;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Landing page.
     */
    public function index(): View
    {
        // Cari election aktif / terbaru
        $activeElection = Election::where('status', ElectionStatus::ACTIVE)
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now())
            ->with(['candidates' => fn($q) => $q->orderBy('no_urut')])
            ->first();

        // Kalau tidak ada aktif, cari yang akan datang / draft
        if (!$activeElection) {
            $activeElection = Election::whereIn('status', [
                ElectionStatus::DRAFT,
                ElectionStatus::ACTIVE,
            ])
                ->orderBy('start_at')
                ->with(['candidates' => fn($q) => $q->orderBy('no_urut')])
                ->first();
        }

        // Election published terbaru (untuk tombol hasil)
        $publishedElection = Election::where('status', ElectionStatus::PUBLISHED)
            ->latest('hasil_published_at')
            ->first();

        return view('home', compact('activeElection', 'publishedElection'));
    }
}
