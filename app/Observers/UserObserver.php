<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    public function saved(User $user): void
    {
        $this->clearCache();
    }

    public function deleted(User $user): void
    {
        $this->clearCache();
    }

    private function clearCache(): void
    {
        Cache::forget('admin.dashboard.stats');
        Cache::forget('admin.live_stats');
    }
}
