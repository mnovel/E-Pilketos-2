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
                            {--start= : Nomor mulai NIS 10 digit (opsional, kalau kosong = random)}
                            {--dry-run : Cek saja tanpa create}';

    protected $description = 'Generate voter dummy untuk testing';

    private const DEFAULT_PASSWORD = 'password';
    private const SAMPLE_LIMIT = 5;
    private const NIS_LENGTH = 10;

    private bool $dryRun = false;

    public function handle(): int
    {
        $jumlah    = (int) $this->argument('jumlah');
        $kelasName = $this->option('kelas');
        $status    = $this->option('status');
        $start     = $this->option('start');

        $this->dryRun = (bool) $this->option('dry-run');

        if ($jumlah <= 0) {
            $this->error('Jumlah harus lebih dari 0.');
            return self::FAILURE;
        }

        $class = ClassRoom::where('name', $kelasName)->first();

        if (!$class) {
            $this->renderClassNotFound($kelasName);
            return self::FAILURE;
        }

        $statusEnum = $this->resolveStatus($status);
        $nisMode    = $start !== null ? "sequential dari {$start}" : 'random 10 digit';

        $this->renderHeader($jumlah, $kelasName, $status, $nisMode);

        $result = $this->generateVoters($jumlah, $class, $statusEnum, $start);

        $this->renderResult($result, $kelasName);

        return self::SUCCESS;
    }

    // ==========================================
    // CORE LOGIC
    // ==========================================

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

            $email = $this->generateUniqueEmail();

            $this->createVoter($nis, $class, $email, $status);

            $created++;
            $this->pushSample($sample, $nis, $email);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        return compact('created', 'skipped', 'sample');
    }

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
     * ✅ Resolve NIS 10 digit.
     *
     * Mode sequential: padLeft 10 digit dengan 0.
     *   --start=10000  → '0000010000', '0000010001', ...
     *   --start=0      → '0000000000', '0000000001', ...
     *
     * Mode random: 10 digit acak murni.
     */
    private function resolveNis(?string $start, int $index): string
    {
        if ($start !== null) {
            return $this->padNis((int) $start + $index);
        }

        return $this->generateUniqueNis();
    }

    /**
     * ✅ Pad angka jadi 10 digit.
     */
    private function padNis(int $number): string
    {
        return str_pad((string) $number, self::NIS_LENGTH, '0', STR_PAD_LEFT);
    }

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
     * ✅ Generate NIS 10 digit unik (random).
     */
    private function generateUniqueNis(int $maxAttempts = 100): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $nis = $this->padNis(random_int(0, 9999999999));

            if (!User::where('nis', $nis)->exists()) {
                return $nis;
            }
        }

        throw new \RuntimeException(
            "Gagal generate NIS unik setelah {$maxAttempts} percobaan."
        );
    }

    /**
     * ✅ Generate email unik dengan format baru: siswa.{3 random}@pilketos.test
     *
     * Contoh: siswa.a4b@pilketos.test, siswa.xyz@pilketos.test
     *
     * Catatan: 3 karakter lowercase+digit = 36^3 = 46,656 kombinasi.
     * Loop sampai unique — collision bisa terjadi kalau generate ribuan voter.
     */
    private function generateUniqueEmail(int $maxAttempts = 100): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $random = strtolower(Str::random(3));
            $email  = "siswa.{$random}@pilketos.test";

            if (!User::where('email', $email)->exists()) {
                return $email;
            }
        }

        throw new \RuntimeException(
            "Gagal generate email unik setelah {$maxAttempts} percobaan."
        );
    }

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
