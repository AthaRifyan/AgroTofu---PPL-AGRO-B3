<?php
// app/Models/Supplier.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    public function mutasiBahan(): HasMany
    {
        return $this->hasMany(StokBahanBaku::class);
    }
}