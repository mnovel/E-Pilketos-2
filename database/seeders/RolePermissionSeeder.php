<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ============ PERMISSIONS ============
        $permissions = [
            // Voter
            'voter.view',
            'voter.verify',
            'voter.reject',
            'voter.delete',
            // Candidate
            'candidate.view',
            'candidate.create',
            'candidate.update',
            'candidate.delete',
            // Election
            'election.view',
            'election.create',
            'election.update',
            'election.delete',
            'election.publish',
            // Session
            'session.view',
            'session.create',
            'session.update',
            'session.delete',
            'session.activate',
            'session.close',
            // Device
            'device.view',
            'device.create',
            'device.update',
            'device.delete',
            // Result
            'result.view',
            'result.export',
            // Device operator
            'device.operate',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ============ ROLES ============
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        $operator = Role::firstOrCreate(['name' => 'operator']);
        $operator->syncPermissions([
            'session.view',
            'device.view',
            'device.operate',
        ]);

        $voter = Role::firstOrCreate(['name' => 'voter']);
        $voter->syncPermissions([
            // voter bisa register & vote (via controller, bukan permission)
        ]);

        $this->command->info('✅ Roles & permissions seeded');
    }
}
