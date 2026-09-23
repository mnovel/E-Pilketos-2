<?php

namespace Tests;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        // Buat role Spatie default
        $this->seedRoles();
    }

    /**
     * Buat role Spatie kalau belum ada.
     */
    protected function seedRoles(): void
    {
        foreach (['admin', 'operator', 'voter'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    /**
     * Bikin admin lengkap dengan role.
     */
    protected function createAdmin(): User
    {
        $user = User::factory()->admin()->create();
        $user->assignRole('admin');
        return $user;
    }

    /**
     * Bikin operator lengkap dengan role.
     */
    protected function createOperator(): User
    {
        $user = User::factory()->operator()->create();
        $user->assignRole('operator');
        return $user;
    }

    /**
     * Bikin voter verified lengkap dengan role.
     */
    protected function createVoter(?ClassRoom $class = null): User
    {
        $class = $class ?? ClassRoom::factory()->create();

        $user = User::factory()->voter()->create([
            'class_id' => $class->id,
            'nis'      => '9' . fake()->unique()->numerify('#######'),
        ]);
        $user->assignRole('voter');
        return $user;
    }
}
