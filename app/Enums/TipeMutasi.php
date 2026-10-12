<?php
// app/Enums/TipeMutasi.php

namespace App\Enums;

enum TipeMutasi: string
{
    case Masuk = 'masuk';
    case Keluar = 'keluar';
    case Penyesuaian = 'penyesuaian';   // stok opname: jumlah = stok fisik hasil hitung (absolut)
}