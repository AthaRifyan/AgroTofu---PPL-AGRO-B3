<?php
// database/seeders/MasterDataSeeder.php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\Produk;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // data contoh: sesuaikan dengan kondisi usaha
        $bahan = [
            // kode, nama, kategori, satuan, stok_minimum
            ['BB-001', 'Kedelai',           'utama',      'kg',    50],
            ['BB-002', 'Air',               'pendukung',  'liter', 0],
            ['BB-003', 'Cuka Penggumpal',   'penggumpal', 'liter', 5],
            ['BB-004', 'Biang Tahu (Ragi)', 'penggumpal', 'liter', 5],
            ['BB-005', 'Garam',             'pendukung',  'kg',    2],
            ['BB-006', 'Kunyit',            'pendukung',  'kg',    1],
        ];

        foreach ($bahan as [$kode, $nama, $kategori, $satuan, $min]) {
            BahanBaku::updateOrCreate(
                ['kode' => $kode],
                ['nama' => $nama, 'kategori' => $kategori, 'satuan' => $satuan, 'stok_minimum' => $min]
            );
        }

        Produk::updateOrCreate(['kode' => 'PRD-TP'], ['nama' => 'Tahu Putih',  'satuan' => 'kg', 'harga_jual' => 20000]);
        Produk::updateOrCreate(['kode' => 'PRD-TK'], ['nama' => 'Tahu Kuning', 'satuan' => 'kg', 'harga_jual' => 22000]);

        Supplier::updateOrCreate(
            ['kode' => 'SUP-001'],
            ['nama' => 'Supplier Kedelai Contoh', 'no_hp' => '081234567890']
        );
    }
}