<?php
// database/factories/BahanBakuFactory.php

namespace Database\Factories;

use App\Models\BahanBaku;
use Illuminate\Database\Eloquent\Factories\Factory;

class BahanBakuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode'           => 'BB-' . fake()->unique()->numerify('####'),
            'nama'           => ucfirst(fake()->unique()->word()),
            'kategori'       => fake()->randomElement(BahanBaku::KATEGORI),
            'satuan'         => fake()->randomElement(['kg', 'liter']),
            'stok_saat_ini'  => fake()->randomFloat(2, 10, 500),
            'stok_minimum'   => 10,
            'harga_terakhir' => fake()->numberBetween(2000, 15000),
            'is_active'      => true,
        ];
    }
}