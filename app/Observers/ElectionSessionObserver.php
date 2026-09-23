<?php

namespace App\Observers;

use App\Models\ElectionSession;
use Illuminate\Support\Facades\Cache;

class ElectionSessionObserver
{
    public function saved(ElectionSession $session): void
    {
        $this->clearCache();
    }

    public function deleted(ElectionSession $session): void
    {
        $this->clearCache();
    }

    private function clearCache(): void
    {
        Cache::forget('admin.live_stats');
        Cache::forget('operator.dashboard');
    }
}
