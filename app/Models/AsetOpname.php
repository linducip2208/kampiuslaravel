<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sesi opname aset: kondisi buku vs fisik + siapa mencatat.
 * Jurnal penyesuaian GL belum otomatis (butuh pemetaan COA) — selisih
 * dilaporkan di ringkasan untuk ditindaklanjuti akuntansi.
 */
class AsetOpname extends Model
{
    protected $table = 'aset_opname';

    public const KONDISI = ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'];

    protected $fillable = ['aset_id', 'kondisi_buku', 'kondisi_fisik', 'catatan', 'user_id'];

    /** @return BelongsTo<Aset, $this> */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }

    public function cocok(): bool
    {
        return $this->kondisi_buku === $this->kondisi_fisik;
    }
}
