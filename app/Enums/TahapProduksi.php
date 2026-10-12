<?php
// app/Enums/TahapProduksi.php

namespace App\Enums;

enum TahapProduksi: string
{
    case Pencucian = 'pencucian';
    case Perendaman = 'perendaman';
    case Penggilingan = 'penggilingan';
    case Perebusan = 'perebusan';
    case Penyaringan = 'penyaringan';
    case Penggumpalan = 'penggumpalan';
    case Pencetakan = 'pencetakan';
    case HasilJadi = 'hasil_jadi';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }

    public function urutan(): int
    {
        return array_search($this, self::cases(), true) + 1;
    }
}