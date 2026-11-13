<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MagangLamaran extends Model
{
    protected $table = 'magang_lamaran';

    protected $fillable = ['lowongan_id', 'mahasiswa_id', 'status', 'catatan'];

    public function lowongan(): BelongsTo
    {
        return $this->belongsTo(MagangLowongan::class, 'lowongan_id');
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
