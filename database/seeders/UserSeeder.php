<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $akun = [
            ['Super Admin',        'admin@tofumind.test',    'admin'],
            ['Manajer Tahu',       'manajer@tofumind.test',  'manajer'],
            ['Karyawan Produksi',  'karyawan@tofumind.test', 'karyawan'],
            ['Kasir Pasar',        'kasir@tofumind.test',    'kasir'],
        ];

        foreach ($akun as [$nama, $email, $role]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                ['name' => $nama, 'password' => bcrypt('password'), 'is_active' => true]
            );
            $user->syncRoles($role);
        }
    }
}