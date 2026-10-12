<?php
// database/factories/ProdukFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProdukFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode'       => 'PRD-' . fake()->unique()->numerify('###'),
            'nama'       => 'Tahu ' . ucfirst(fake()->word()),
            'satuan'     => 'kg',
            'harga_jual' => fake()->numberBetween(15000, 30000),
            'stok'       => 0,
            'is_active'  => true,
        ];
    }
}