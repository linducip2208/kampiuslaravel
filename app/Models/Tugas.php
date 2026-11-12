<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tugas extends Model
{
    protected $table = 'tugas';

    protected $fillable = ['kelas_id', 'pertemuan_id', 'judul', 'deskripsi', 'deadline'];

    protected function casts(): array
    {
        return ['deadline' => 'datetime'];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function pertemuan(): BelongsTo
    {
        return $this->belongsTo(Pertemuan::class);
    }

    public function pengumpulan(): HasMany
    {
        return $this->hasMany(TugasKumpul::class);
    }
}
