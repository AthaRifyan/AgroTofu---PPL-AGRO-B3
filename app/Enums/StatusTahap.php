<?php
// app/Enums/StatusTahap.php

namespace App\Enums;

enum StatusTahap: string
{
    case Menunggu = 'menunggu';
    case Berjalan = 'berjalan';
    case Selesai = 'selesai';
}
