<?php

namespace Database\Seeders;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\Candidate;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Database\Seeder;

class FullPilketosSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🗳️  Mulai seeding Pilketos...');
        $this->command->newLine();

        // ==========================================
        // 1. ELECTION
        // ==========================================
        $admin = User::where('role', UserRole::ADMIN)->first();

        if (!$admin) {
            $this->command->error('❌ Admin tidak ditemukan! Jalankan AdminSeeder dulu.');
            return;
        }

        // ⏰ Election: mulai 5 menit dari sekarang, selesai 1 jam dari sekarang
        $electionStart = now()->addMinutes(5);
        $electionEnd   = now()->addHour();

        $election = Election::updateOrCreate(
            ['title' => 'Pemilihan Ketua OSIS 2026'],
            [
                'tahun_ajaran' => '2025/2026',
                'deskripsi'    => 'Pemilihan Ketua OSIS periode 2025/2026 secara digital.',
                'start_at'     => $electionStart,
                'end_at'       => $electionEnd,
                'status'       => ElectionStatus::DRAFT,   // ← draft, akan auto-activate via scheduler
                'created_by'   => $admin->id,
            ]
        );

        $this->command->info("✅ Election #{$election->id} — {$election->title}");
        $this->command->line("   Periode: {$election->start_at->format('d M Y H:i')} s/d {$election->end_at->format('d M Y H:i')}");
        $this->command->line("   Status : DRAFT — akan auto-activate dalam 5 menit (tunggu scheduler)");
        $this->command->newLine();

        // ==========================================
        // 2. CANDIDATES
        // ==========================================
        $candidatesData = [
            [
                'no_urut' => 1,
                'nama'    => 'Ahmad Fauzi',
                'kelas'   => 'XI-IPA-1',
                'visi'    => 'Mewujudkan OSIS yang aktif, inovatif, dan berprestasi.',
                'misi'    => "1. Mengadakan program rutin bulanan\n2. Meningkatkan kegiatan ekstrakurikuler\n3. Membangun komunikasi antar siswa yang lebih baik",
                'program' => "1. OSIS Fest tahunan\n2. Kelas Inspirasi\n3. Bakti sosial rutin",
            ],
            [
                'no_urut' => 2,
                'nama'    => 'Siti Nurhaliza',
                'kelas'   => 'XI-IPA-2',
                'visi'    => 'OSIS sebagai wadah kreativitas dan pengembangan diri siswa.',
                'misi'    => "1. Menyediakan wadah untuk bakat dan minat siswa\n2. Mengadakan pelatihan kepemimpinan\n3. Mempererat tali persaudaraan antar kelas",
                'program' => "1. Pelatihan public speaking\n2. Kompetisi antar kelas\n3. Mentoring siswa baru",
            ],
            [
                'no_urut' => 3,
                'nama'    => 'Budi Santoso',
                'kelas'   => 'XI-IPS-1',
                'visi'    => 'Membangun OSIS yang responsif dan dekat dengan siswa.',
                'misi'    => "1. Membuka forum aspirasi siswa\n2. Meningkatkan fasilitas sekolah\n3. Mengadakan kegiatan yang melibatkan seluruh siswa",
                'program' => "1. Kotak saran digital\n2. Perbaikan kantin sekolah\n3. Class Meeting",
            ],
        ];

        foreach ($candidatesData as $data) {
            $class = ClassRoom::where('name', $data['kelas'])->first();

            Candidate::updateOrCreate(
                [
                    'election_id' => $election->id,
                    'no_urut'     => $data['no_urut'],
                ],
                [
                    'nama'          => $data['nama'],
                    'class_id'      => $class?->id,
                    'visi'          => $data['visi'],
                    'misi'          => $data['misi'],
                    'program_kerja' => $data['program'],
                ]
            );

            $this->command->info("  ✅ Kandidat #{$data['no_urut']}: {$data['nama']} ({$data['kelas']})");
        }
        $this->command->newLine();

        // ==========================================
        // 3. ELECTION SESSIONS
        // ==========================================
        // Sesi @2 menit, staggered dalam range election
        //
        // Timeline:
        // +5  → +7   : X-IPA-1, X-IPA-2   (2 sesi paralel)
        // +10 → +12  : X-IPA-3
        // +15 → +17  : X-IPS-1
        // +20 → +22  : XI-IPA-1
        //
        // Semua di-set SCHEDULED → auto-activate via scheduler

        $sessionsData = [
            [
                'kelas'         => 'X-IPA-1',
                'mulai_offset'  => 5,     // +5 menit
                'selesai_offset' => 7,     // +7 menit
            ],
            [
                'kelas'         => 'X-IPA-2',
                'mulai_offset'  => 5,     // paralel dengan X-IPA-1
                'selesai_offset' => 7,
            ],
            [
                'kelas'         => 'X-IPA-3',
                'mulai_offset'  => 10,
                'selesai_offset' => 12,
            ],
            [
                'kelas'         => 'X-IPS-1',
                'mulai_offset'  => 15,
                'selesai_offset' => 17,
            ],
            [
                'kelas'         => 'XI-IPA-1',
                'mulai_offset'  => 20,
                'selesai_offset' => 22,
            ],
        ];

        foreach ($sessionsData as $data) {
            $class = ClassRoom::where('name', $data['kelas'])->first();

            if (!$class) {
                $this->command->warn("  ⚠️  Kelas {$data['kelas']} tidak ditemukan, skip.");
                continue;
            }

            // Hitung waktu berdasarkan offset
            $mulaiWaktu   = now()->addMinutes($data['mulai_offset']);
            $selesaiWaktu = now()->addMinutes($data['selesai_offset']);

            $session = ElectionSession::updateOrCreate(
                [
                    'election_id' => $election->id,
                    'class_id'    => $class->id,
                ],
                [
                    'tanggal'       => $mulaiWaktu->format('Y-m-d'),
                    'waktu_mulai'   => $mulaiWaktu->format('H:i'),
                    'waktu_selesai' => $selesaiWaktu->format('H:i'),
                    'status'        => SessionStatus::SCHEDULED,   // ← auto-activate nanti
                    'operator_id'   => $admin->id,
                    'activated_at'  => null,
                ]
            );

            $this->command->info("  ✅ Sesi {$data['kelas']}: {$mulaiWaktu->format('H:i')} – {$selesaiWaktu->format('H:i')} [scheduled]");

            $count = $this->assignVotersToSession($session);
            if ($count > 0) {
                $this->command->line("     → {$count} pemilih di-assign");
            }
        }
        $this->command->newLine();

        // ==========================================
        // 4. TIMELINE PREVIEW
        // ==========================================
        $this->command->info('⏰ TIMELINE (relative ke sekarang):');
        $this->command->newLine();

        $this->command->line('  <fg=cyan>+5m  → +7m </>  Election START + Sesi X-IPA-1 & X-IPA-2 auto-active');
        $this->command->line('  <fg=cyan>+7m        </>  X-IPA-1 & X-IPA-2 auto-close');
        $this->command->line('  <fg=cyan>+10m → +12m</>  Sesi X-IPA-3 auto-active & auto-close');
        $this->command->line('  <fg=cyan>+15m → +17m</>  Sesi X-IPS-1 auto-active & auto-close');
        $this->command->line('  <fg=cyan>+20m → +22m</>  Sesi XI-IPA-1 auto-active & auto-close');
        $this->command->line('  <fg=cyan>+60m       </>  Election auto-close');
        $this->command->newLine();

        // ==========================================
        // 5. SUMMARY
        // ==========================================
        $this->command->info('📊 SUMMARY:');
        $this->command->table(
            ['Item', 'Jumlah'],
            [
                ['Election',         Election::count()],
                ['Candidates',       Candidate::where('election_id', $election->id)->count()],
                ['Sessions',         ElectionSession::where('election_id', $election->id)->count()],
                ['Sessions Active',  ElectionSession::where('election_id', $election->id)->where('status', 'active')->count()],
                ['Total Voters',     Voter::where('election_id', $election->id)->count()],
            ]
        );

        $this->command->newLine();
        $this->command->info('✅ Seeding Pilketos selesai!');
        $this->command->newLine();

        $this->command->line('<fg=yellow>📝 Cara Test:</fg=yellow>');
        $this->command->line('  1. Jalankan scheduler: <fg=green>php artisan schedule:work</>');
        $this->command->line('  2. Tunggu 5 menit → election & 2 sesi auto-active');
        $this->command->line('  3. Login operator → buka /device/checkin → QR muncul');
        $this->command->line('  4. Login voter → scan QR → check-in');
        $this->command->newLine();
        $this->command->line('<fg=red>⚠️  WAJIB: jalankan scheduler paralel!</fg=red>');
        $this->command->line('   php artisan schedule:work');
        $this->command->newLine();
    }

    /**
     * Assign voters ke session berdasarkan kelas.
     */
    private function assignVotersToSession(ElectionSession $session): int
    {
        $users = User::where('role', UserRole::VOTER)
            ->where('status', VoterStatus::VERIFIED)
            ->where('class_id', $session->class_id)
            ->get();

        $count = 0;

        foreach ($users as $user) {
            $voter = Voter::updateOrCreate(
                [
                    'election_id' => $session->election_id,
                    'user_id'     => $user->id,
                ],
                [
                    'session_id' => $session->id,
                    'class_id'   => $session->class_id,
                ]
            );

            if ($voter->wasRecentlyCreated || $voter->wasChanged('session_id')) {
                $count++;
            }
        }

        return $count;
    }
}
