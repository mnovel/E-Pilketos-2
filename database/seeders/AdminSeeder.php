<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Cari class_id untuk voter contoh
        $class = ClassRoom::where('name', 'X-IPA-1')->first();

        // Admin default
        $admin = User::firstOrCreate(
            ['email' => 'admin@pilketos.test'],
            [
                'name'     => 'Administrator',
                'password' => Hash::make('password'),
                'role'     => UserRole::ADMIN,
                'status'   => VoterStatus::VERIFIED,
            ]
        );
        $admin->assignRole('admin');

        // Operator default
        $operator = User::firstOrCreate(
            ['email' => 'operator@pilketos.test'],
            [
                'name'     => 'Operator Pilketos',
                'password' => Hash::make('password'),
                'role'     => UserRole::OPERATOR,
                'status'   => VoterStatus::VERIFIED,
            ]
        );
        $operator->assignRole('operator');

        // Voter contoh
        $voter = User::firstOrCreate(
            ['email' => 'siswa@pilketos.test'],
            [
                'nis'      => '12345',
                'name'     => 'Ahmad Fauzi',
                'class_id' => $class?->id,
                'password' => Hash::make('password'),
                'role'     => UserRole::VOTER,
                'status'   => VoterStatus::VERIFIED,
            ]
        );
        $voter->assignRole('voter');

        $this->command->info('✅ Default users seeded');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin',    'admin@pilketos.test',    'password'],
                ['Operator', 'operator@pilketos.test', 'password'],
                ['Voter',    'siswa@pilketos.test',    'password'],
            ]
        );
    }
}
