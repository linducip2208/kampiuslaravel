<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tagihan extends Model
{
    use HasFactory;

    protected $table = 'tagihan';

    protected $fillable = ['mahasiswa_id', 'tahun_akademik_id', 'jenis', 'nominal', 'terbayar', 'status'];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    /** @return HasMany<Pembayaran> */
    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function sisa(): int
    {
        return max(0, (int) $this->nominal - (int) $this->terbayar);
    }
}
