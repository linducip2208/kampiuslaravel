<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MagangLowongan extends Model
{
    protected $table = 'magang_lowongan';

    protected $fillable = ['mitra_id', 'judul', 'deskripsi', 'kuota', 'deadline', 'status'];

    protected $casts = ['deadline' => 'date'];

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(MbkmMitra::class, 'mitra_id');
    }

    public function lamaran(): HasMany
    {
        return $this->hasMany(MagangLamaran::class, 'lowongan_id');
    }
}
