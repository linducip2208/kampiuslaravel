<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkripsiJudul extends Model
{
    use HasFactory;

    protected $table = 'skripsi_judul';

    protected $fillable = ['mahasiswa_id', 'judul', 'deskripsi', 'status', 'pembimbing1_id', 'pembimbing2_id', 'topik_id', 'similarity', 'plagiarisme_status', 'plagiarisme_laporan'];

    protected function casts(): array
    {
        return ['similarity' => 'float'];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function pembimbing1(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'pembimbing1_id');
    }

    public function pembimbing2(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'pembimbing2_id');
    }

    public function topik(): BelongsTo
    {
        return $this->belongsTo(SkripsiTopik::class, 'topik_id');
    }

    /** @return HasMany<Bimbingan> */
    public function bimbingans(): HasMany
    {
        return $this->hasMany(Bimbingan::class, 'judul_id');
    }

    /** @return HasMany<Sidang> */
    public function sidangs(): HasMany
    {
        return $this->hasMany(Sidang::class, 'judul_id');
    }
}
