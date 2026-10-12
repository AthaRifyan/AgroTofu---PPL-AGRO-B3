<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RoleSeeder::class, UserSeeder::class, MasterDataSeeder::class]);

        if (app()->environment('local')) {
            Supplier::factory()->count(3)->create();
        }
    }
}