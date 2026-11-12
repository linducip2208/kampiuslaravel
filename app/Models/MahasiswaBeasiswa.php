<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MahasiswaBeasiswa extends Model
{
    protected $table = 'mahasiswa_beasiswa';

    protected $fillable = ['mahasiswa_id', 'beasiswa_id', 'tahun_akademik_id', 'status'];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function beasiswa(): BelongsTo
    {
        return $this->belongsTo(Beasiswa::class);
    }
}
