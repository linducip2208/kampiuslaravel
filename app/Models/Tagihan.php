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

    protected $fillable = ['mahasiswa_id', 'tahun_akademik_id', 'jenis', 'nominal', 'terbayar', 'status', 'beasiswa_id', 'diskon', 'jatuh_tempo', 'denda', 'notif_h7_at', 'notif_hp7_at'];

    protected function casts(): array
    {
        return ['jatuh_tempo' => 'date', 'notif_h7_at' => 'datetime', 'notif_hp7_at' => 'datetime'];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    public function beasiswa(): BelongsTo
    {
        return $this->belongsTo(Beasiswa::class);
    }

    /** @return HasMany<Pembayaran> */
    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    /** @return HasMany<TagihanCicilan> */
    public function cicilan(): HasMany
    {
        return $this->hasMany(TagihanCicilan::class)->orderBy('urutan');
    }

    public function sisa(): int
    {
        return max(0, $this->totalTagihan() - (int) $this->terbayar);
    }

    public function totalTagihan(): int
    {
        return (int) $this->nominal + (int) $this->denda;
    }

    public function hariTerlambat(): int
    {
        if (! $this->jatuh_tempo) {
            return 0;
        }

        $diff = now()->startOfDay()->diffInDays($this->jatuh_tempo->startOfDay(), false);

        return $diff < 0 ? (int) abs($diff) : 0;
    }
}
