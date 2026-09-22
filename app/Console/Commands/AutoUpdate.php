<?php

namespace App\Console\Commands;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Models\Election;
use App\Models\ElectionSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutoUpdate extends Command
{
    protected $signature = 'pilketos:auto-update
                            {--dry-run : Cek saja tanpa update}';

    protected $description = 'Auto-activate & auto-close election/session berdasarkan waktu';

    private bool $dryRun = false;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        $this->info('🔄 Auto-update election & session...');
        $this->newLine();

        $summary = [
            'activated_elections' => $this->activateElections(),
            'activated_sessions'  => $this->activateSessions(),
            'closed_elections'    => $this->closeElections(),
            'closed_sessions'     => $this->closeSessions(),
        ];

        $this->renderSummary($summary);

        return self::SUCCESS;
    }

    // ==========================================
    // STEP 1 — AUTO-ACTIVATE ELECTIONS
    // ==========================================

    /**
     * Draft → Active.
     * Syarat: waktu mulai tiba, waktu belum berakhir, minimal 2 kandidat.
     */
    private function activateElections(): int
    {
        $this->sectionHeader('[1/4] Auto-activate election (draft → active)');

        $count = 0;

        $elections = Election::where('status', ElectionStatus::DRAFT)
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now())
            ->get();

        foreach ($elections as $election) {
            $candidateCount = $election->candidates()->count();

            if ($candidateCount < 2) {
                $this->skip(
                    "Election #{$election->id} — {$election->title}",
                    "kandidat kurang ({$candidateCount}/2)"
                );
                continue;
            }

            $this->apply(
                action: fn() => $election->update(['status' => ElectionStatus::ACTIVE]),
                logMessage: "Election #{$election->id} auto-activated ({$election->title})"
            );

            $this->success("Election #{$election->id} — {$election->title}");
            $count++;
        }

        return $count;
    }

    // ==========================================
    // STEP 2 — AUTO-ACTIVATE SESSIONS
    // ==========================================

    /**
     * Scheduled → Active.
     * Syarat: election active dalam range waktu, session dalam range waktu.
     */
    private function activateSessions(): int
    {
        $this->sectionHeader('[2/4] Auto-activate session (scheduled → active)');

        $count = 0;

        $sessions = ElectionSession::where('status', SessionStatus::SCHEDULED)
            ->whereHas('election', function ($q) {
                $q->where('status', ElectionStatus::ACTIVE)
                    ->where('start_at', '<=', now())
                    ->where('end_at', '>', now());
            })
            ->with(['election', 'classRoom'])
            ->get();

        foreach ($sessions as $session) {
            if (!$session->isWithinTimeWindow()) {
                $this->skip(
                    "Session #{$session->id} — {$session->classRoom?->name}",
                    'di luar range waktu'
                );
                continue;
            }

            $this->apply(
                action: fn() => $session->update([
                    'status'       => SessionStatus::ACTIVE,
                    'activated_at' => now(),
                ]),
                logMessage: "Session #{$session->id} auto-activated ({$session->classRoom?->name})"
            );

            $this->success("Session #{$session->id} — {$session->classRoom?->name}");
            $count++;
        }

        return $count;
    }

    // ==========================================
    // STEP 3 — AUTO-CLOSE ELECTIONS
    // ==========================================

    /**
     * Active/Draft → Closed.
     * Syarat: end_at sudah lewat.
     */
    private function closeElections(): int
    {
        $this->sectionHeader('[3/4] Auto-close election (active/draft → closed)');

        $count = 0;

        $elections = Election::whereIn('status', [
            ElectionStatus::ACTIVE,
            ElectionStatus::DRAFT,
        ])
            ->where('end_at', '<', now())
            ->get();

        foreach ($elections as $election) {
            $this->apply(
                action: fn() => $election->update(['status' => ElectionStatus::CLOSED]),
                logMessage: "Election #{$election->id} auto-closed ({$election->title})"
            );

            $this->closed("Election #{$election->id} — {$election->title}");
            $count++;
        }

        return $count;
    }

    // ==========================================
    // STEP 4 — AUTO-CLOSE SESSIONS
    // ==========================================

    /**
     * Active/Scheduled → Closed.
     * Syarat: waktu selesai sudah lewat.
     */
    private function closeSessions(): int
    {
        $this->sectionHeader('[4/4] Auto-close session (active/scheduled → closed)');

        $count = 0;

        $sessions = ElectionSession::whereIn('status', [
            SessionStatus::ACTIVE,
            SessionStatus::SCHEDULED,
        ])
            ->with('classRoom')
            ->get();

        foreach ($sessions as $session) {
            if (!$session->hasEnded()) {
                continue;
            }

            $label = $session->classRoom?->name ?? '-';

            $this->apply(
                action: fn() => $session->update([
                    'status'    => SessionStatus::CLOSED,
                    'closed_at' => now(),
                ]),
                logMessage: "Session #{$session->id} auto-closed ({$label})"
            );

            $this->closed("Session #{$session->id} — {$label}");
            $count++;
        }

        return $count;
    }

    // ==========================================
    // HELPERS — OUTPUT
    // ==========================================

    private function sectionHeader(string $text): void
    {
        $this->line("<fg=cyan>▶ {$text}...</>");
    }

    private function success(string $text): void
    {
        $this->line("  <fg=green>✅</> {$text}");
    }

    private function closed(string $text): void
    {
        $this->line("  <fg=red>❌</> {$text}");
    }

    private function skip(string $text, string $reason): void
    {
        $this->line("  <fg=yellow>⏸️</>  {$text} <fg=gray>({$reason})</>");
    }

    // ==========================================
    // HELPERS — EXECUTION
    // ==========================================

    /**
     * Jalankan aksi kalau bukan dry-run, catat log.
     * Kalau error → tangkap & tampil, tidak crash.
     */
    private function apply(callable $action, string $logMessage): void
    {
        if ($this->dryRun) {
            return;
        }

        try {
            $action();
            Log::info($logMessage);
        } catch (Throwable $e) {
            Log::error("AutoUpdate failed: {$logMessage} — {$e->getMessage()}");
            $this->line("  <fg=red>⚠️  Error:</> {$logMessage} ({$e->getMessage()})");
        }
    }

    // ==========================================
    // SUMMARY
    // ==========================================

    private function renderSummary(array $summary): void
    {
        $this->newLine();

        $title = $this->dryRun
            ? '🧪 DRY-RUN Summary:'
            : '✅ Summary:';

        $this->info($title);

        $this->table(
            ['Action', 'Count'],
            [
                ['Activated elections', $summary['activated_elections']],
                ['Activated sessions',  $summary['activated_sessions']],
                ['Closed elections',    $summary['closed_elections']],
                ['Closed sessions',     $summary['closed_sessions']],
            ]
        );
    }
}
