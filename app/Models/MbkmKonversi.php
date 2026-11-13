<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MbkmKonversi extends Model
{
    protected $table = 'mbkm_konversi';

    protected $fillable = ['mahasiswa_id', 'mitra_id', 'jenis_kegiatan', 'matakuliah_id', 'sks_diakui', 'huruf', 'status'];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(MbkmMitra::class, 'mitra_id');
    }

    public function matakuliah(): BelongsTo
    {
        return $this->belongsTo(Matakuliah::class);
    }

    public function logbook(): HasMany
    {
        return $this->hasMany(MbkmLogbook::class, 'konversi_id');
    }
}
