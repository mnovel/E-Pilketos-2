<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenerateTestVoters extends Command
{
    protected $signature = 'pilketos:generate-voters
                            {jumlah=55 : Jumlah voter yang akan dibuat}
                            {--kelas=X-IPA-1 : Nama kelas}
                            {--status=pending : pending|verified|rejected}
                            {--start= : Nomor mulai NIS (opsional, kalau kosong = random)}
                            {--dry-run : Cek saja tanpa create}';

    protected $description = 'Generate voter dummy untuk testing';

    private const DEFAULT_PASSWORD = 'password';
    private const SAMPLE_LIMIT = 5;

    private const EMAIL_DOMAINS = [
        'test.com',
        'mail.test',
        'dummy.local',
        'siswa.test',
        'pilketos.test',
    ];

    private bool $dryRun = false;

    public function handle(): int
    {
        // ==========================================
        // VALIDASI INPUT
        // ==========================================
        $jumlah    = (int) $this->argument('jumlah');
        $kelasName = $this->option('kelas');
        $status    = $this->option('status');
        $start     = $this->option('start');

        $this->dryRun = (bool) $this->option('dry-run');

        if ($jumlah <= 0) {
            $this->error('Jumlah harus lebih dari 0.');
            return self::FAILURE;
        }

        // ==========================================
        // CARI KELAS
        // ==========================================
        $class = ClassRoom::where('name', $kelasName)->first();

        if (!$class) {
            $this->renderClassNotFound($kelasName);
            return self::FAILURE;
        }

        // ==========================================
        // TENTUKAN STATUS & MODE
        // ==========================================
        $statusEnum = $this->resolveStatus($status);
        $nisMode    = $start !== null ? "sequential dari {$start}" : "random";

        // ==========================================
        // HEADER
        // ==========================================
        $this->renderHeader($jumlah, $kelasName, $status, $nisMode);

        // ==========================================
        // PROSES GENERATE
        // ==========================================
        $result = $this->generateVoters($jumlah, $class, $statusEnum, $start);

        // ==========================================
        // RENDER HASIL
        // ==========================================
        $this->renderResult($result, $kelasName);

        return self::SUCCESS;
    }

    // ==========================================
    // CORE LOGIC
    // ==========================================

    /**
     * Loop generate voter.
     */
    private function generateVoters(
        int $jumlah,
        ClassRoom $class,
        VoterStatus $status,
        ?string $start
    ): array {
        $bar = $this->output->createProgressBar($jumlah);
        $bar->start();

        $created = 0;
        $skipped = 0;
        $sample  = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $nis = $this->resolveNis($start, $i);

            if (User::where('nis', $nis)->exists()) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if ($this->dryRun) {
                $created++;
                $this->pushSample($sample, $nis, '(dry-run)');
                $bar->advance();
                continue;
            }

            $email = $this->generateUniqueEmail($nis);

            $user = $this->createVoter($nis, $class, $email, $status);

            $created++;
            $this->pushSample($sample, $nis, $email);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        return compact('created', 'skipped', 'sample');
    }

    /**
     * Bikin user voter baru.
     */
    private function createVoter(
        string $nis,
        ClassRoom $class,
        string $email,
        VoterStatus $status
    ): User {
        $user = User::create([
            'nis'      => $nis,
            'name'     => 'Siswa Test ' . $nis,
            'class_id' => $class->id,
            'email'    => $email,
            'password' => Hash::make(self::DEFAULT_PASSWORD),
            'role'     => UserRole::VOTER,
            'status'   => $status,
        ]);

        try {
            if (!$user->hasRole('voter')) {
                $user->assignRole('voter');
            }
        } catch (\Throwable $e) {
            // skip — role belum di-seed
        }

        return $user;
    }

    /**
     * Resolve NIS berdasarkan mode.
     */
    private function resolveNis(?string $start, int $index): string
    {
        if ($start !== null) {
            return '9' . str_pad((string) ($start + $index), 4, '0', STR_PAD_LEFT);
        }

        return $this->generateUniqueNis();
    }

    /**
     * Simpan sample (max N).
     */
    private function pushSample(array &$sample, string $nis, string $email): void
    {
        if (count($sample) < self::SAMPLE_LIMIT) {
            $sample[] = ['nis' => $nis, 'email' => $email];
        }
    }

    // ==========================================
    // GENERATOR HELPERS
    // ==========================================

    /**
     * Generate NIS unik & random (9 + 5 digit).
     */
    private function generateUniqueNis(int $maxAttempts = 100): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $nis = '9' . random_int(10000, 99999);

            if (!User::where('nis', $nis)->exists()) {
                return $nis;
            }
        }

        throw new \RuntimeException(
            "Gagal generate NIS unik setelah {$maxAttempts} percobaan. "
                . "Kemungkinan NIS sudah penuh."
        );
    }

    /**
     * Generate email unik.
     */
    private function generateUniqueEmail(string $nis, int $maxAttempts = 100): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $random = strtolower(Str::random(4));
            $domain = self::EMAIL_DOMAINS[array_rand(self::EMAIL_DOMAINS)];
            $email  = "siswa-{$nis}-{$random}@{$domain}";

            if (!User::where('email', $email)->exists()) {
                return $email;
            }
        }

        throw new \RuntimeException(
            "Gagal generate email unik untuk NIS {$nis} setelah {$maxAttempts} percobaan."
        );
    }

    /**
     * Resolve status enum dari string.
     */
    private function resolveStatus(string $status): VoterStatus
    {
        return match ($status) {
            'pending'  => VoterStatus::PENDING,
            'verified' => VoterStatus::VERIFIED,
            'rejected' => VoterStatus::REJECTED,
            default    => VoterStatus::PENDING,
        };
    }

    // ==========================================
    // RENDER HELPERS
    // ==========================================

    private function renderHeader(
        int $jumlah,
        string $kelasName,
        string $status,
        string $nisMode
    ): void {
        $dryTag = $this->dryRun ? ' <fg=yellow>[DRY-RUN]</>' : '';

        $this->info("Generating {$jumlah} voter untuk {$kelasName}{$dryTag}");
        $this->line("   Status : {$status}");
        $this->line("   NIS    : {$nisMode}");
        $this->newLine();
    }

    private function renderClassNotFound(string $kelasName): void
    {
        $this->error("Kelas \"{$kelasName}\" tidak ditemukan.");
        $this->newLine();
        $this->info('Kelas tersedia:');

        $this->table(
            ['ID', 'Nama', 'Tingkat'],
            ClassRoom::active()
                ->orderBy('tingkat')
                ->orderBy('name')
                ->get(['id', 'name', 'tingkat'])
                ->toArray()
        );
    }

    private function renderResult(array $result, string $kelasName): void
    {
        $verb = $this->dryRun ? 'akan dibuat' : 'berhasil dibuat';

        $this->info("✅ {$result['created']} voter {$verb} untuk {$kelasName}!");

        if ($result['skipped'] > 0) {
            $this->warn("⚠️  {$result['skipped']} voter dilewati (NIS sudah ada).");
        }

        if ($this->dryRun && $result['created'] > 0) {
            $this->newLine();
            $this->line('<fg=yellow>Dry-run mode — tidak ada data yang disimpan.</>');
        }

        if (!empty($result['sample'])) {
            $this->newLine();
            $label = count($result['sample']) === self::SAMPLE_LIMIT
                ? 'Sample ' . self::SAMPLE_LIMIT . ' voter pertama:'
                : 'Sample voter:';

            $this->line("📋 {$label}");
            $this->table(
                ['NIS', 'Email'],
                collect($result['sample'])
                    ->map(fn($row) => [$row['nis'], $row['email']])
                    ->toArray()
            );
        }

        $this->newLine();
    }
}
