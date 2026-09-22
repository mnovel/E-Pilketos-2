<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            // Tingkat X
            ['X-IPA-1', 'X', 'IPA', '1'],
            ['X-IPA-2', 'X', 'IPA', '2'],
            ['X-IPA-3', 'X', 'IPA', '3'],
            ['X-IPS-1', 'X', 'IPS', '1'],
            ['X-IPS-2', 'X', 'IPS', '2'],

            // Tingkat XI
            ['XI-IPA-1', 'XI', 'IPA', '1'],
            ['XI-IPA-2', 'XI', 'IPA', '2'],
            ['XI-IPA-3', 'XI', 'IPA', '3'],
            ['XI-IPS-1', 'XI', 'IPS', '1'],
            ['XI-IPS-2', 'XI', 'IPS', '2'],

            // Tingkat XII
            ['XII-IPA-1', 'XII', 'IPA', '1'],
            ['XII-IPA-2', 'XII', 'IPA', '2'],
            ['XII-IPA-3', 'XII', 'IPA', '3'],
            ['XII-IPS-1', 'XII', 'IPS', '1'],
            ['XII-IPS-2', 'XII', 'IPS', '2'],

            ['GURU & TU', 'GURU', null, null],
        ];

        foreach ($classes as [$name, $tingkat, $jurusan, $rombel]) {
            ClassRoom::firstOrCreate(
                ['name' => $name],
                [
                    'tingkat'   => $tingkat,
                    'jurusan'   => $jurusan,
                    'rombel'    => $rombel,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✅ ' . count($classes) . ' kelas berhasil di-seed');
    }
}
