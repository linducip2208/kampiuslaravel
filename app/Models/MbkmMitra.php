<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MbkmMitra extends Model
{
    protected $table = 'mbkm_mitra';

    protected $fillable = ['nama', 'jenis', 'alamat', 'kontak', 'email', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    public function lowongan(): HasMany
    {
        return $this->hasMany(MagangLowongan::class, 'mitra_id');
    }

    public function konversi(): HasMany
    {
        return $this->hasMany(MbkmKonversi::class, 'mitra_id');
    }
}
