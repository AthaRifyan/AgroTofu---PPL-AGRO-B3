<?php
// database/factories/SupplierFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode'          => 'SUP-' . fake()->unique()->numerify('####'),
            'nama'          => 'UD ' . fake()->lastName(),
            'kontak_person' => fake()->name(),
            'no_hp'         => fake()->numerify('08##########'),
            'alamat'        => fake()->address(),
            'is_active'     => true,
        ];
    }
}