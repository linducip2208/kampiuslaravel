<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sidang extends Model
{
    use HasFactory;

    protected $table = 'sidang';

    protected $fillable = ['judul_id', 'jenis', 'tanggal', 'ruang_id', 'berita_acara_path', 'nilai', 'catatan_revisi', 'status'];

    protected function casts(): array
    {
        return ['tanggal' => 'datetime', 'nilai' => 'decimal:2'];
    }

    public function judul(): BelongsTo
    {
        return $this->belongsTo(SkripsiJudul::class, 'judul_id');
    }

    public function ruang(): BelongsTo
    {
        return $this->belongsTo(Ruang::class);
    }

    /** @return HasMany<SidangPenguji> */
    public function pengujis(): HasMany
    {
        return $this->hasMany(SidangPenguji::class);
    }
}
