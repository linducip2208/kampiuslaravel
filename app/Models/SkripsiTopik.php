<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkripsiTopik extends Model
{
    use HasFactory;

    protected $table = 'skripsi_topik';

    protected $fillable = ['dosen_id', 'judul', 'deskripsi', 'bidang', 'kuota', 'diambil', 'status'];

    protected function casts(): array
    {
        return ['kuota' => 'integer', 'diambil' => 'integer'];
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    /** @return HasMany<SkripsiJudul> */
    public function pengajuan(): HasMany
    {
        return $this->hasMany(SkripsiJudul::class, 'topik_id');
    }

    public function getSisaAttribute(): int
    {
        return max(0, (int) $this->kuota - (int) $this->diambil);
    }

    public function isTersedia(): bool
    {
        return $this->status === 'buka' && $this->sisa > 0;
    }
}
