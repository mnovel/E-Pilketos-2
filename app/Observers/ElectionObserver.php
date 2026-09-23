<?php

namespace App\Observers;

use App\Models\Election;
use Illuminate\Support\Facades\Cache;

class ElectionObserver
{
    /**
     * Setiap kali election disimpan (create / update) → clear cache.
     */
    public function saved(Election $election): void
    {
        $this->clearCache();
    }

    /**
     * Setiap kali election dihapus → clear cache.
     */
    public function deleted(Election $election): void
    {
        $this->clearCache();
    }

    /**
     * Setiap kali election di-restore (SoftDeletes) → clear cache.
     */
    public function restored(Election $election): void
    {
        $this->clearCache();
    }

    private function clearCache(): void
    {
        Cache::forget('home.active_election');
        Cache::forget('home.published_election');
        Cache::forget('admin.dashboard.stats');
        Cache::forget('admin.live_stats');
    }
}
