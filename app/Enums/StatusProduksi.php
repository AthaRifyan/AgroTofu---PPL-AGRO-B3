<?php
// app/Enums/StatusProduksi.php

namespace App\Enums;

enum StatusProduksi: string
{
    case Terjadwal = 'terjadwal';
    case Berjalan = 'berjalan';
    case Selesai = 'selesai';
    case Batal = 'batal';
}