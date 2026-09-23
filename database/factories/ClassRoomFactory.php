<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClassRoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'      => 'X-IPA-' . fake()->unique()->numberBetween(1, 100),
            'tingkat'   => 'X',
            'jurusan'   => 'IPA',
            'rombel'    => '1',
            'is_active' => true,
        ];
    }
}
