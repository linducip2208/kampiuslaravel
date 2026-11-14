<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aset extends Model
{
    protected $table = 'aset';

    protected $fillable = ['kode', 'nama', 'kategori', 'lokasi', 'kondisi', 'tgl_beli', 'nilai', 'umur_manfaat_bulan', 'akumulasi_penyusutan', 'terakhir_susut_at'];

    protected function casts(): array
    {
        return ['tgl_beli' => 'date', 'nilai' => 'integer', 'terakhir_susut_at' => 'date'];
    }

    public function maintenance(): HasMany
    {
        return $this->hasMany(AsetMaintenance::class);
    }

    public function totalBiayaMaintenance(): int
    {
        return (int) $this->maintenance()->sum('biaya');
    }
}
