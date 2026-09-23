<?php

namespace App\Observers;

use App\Models\Candidate;
use Illuminate\Support\Facades\Cache;

class CandidateObserver
{
    public function saved(Candidate $candidate): void
    {
        Cache::forget('home.active_election');
    }

    public function deleted(Candidate $candidate): void
    {
        Cache::forget('home.active_election');
    }

    public function restored(Candidate $candidate): void
    {
        Cache::forget('home.active_election');
    }
}
