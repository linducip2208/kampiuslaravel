<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Snapshot rincian KRS sebelum ditimpa submit ulang (riwayat revisi).
 */
class KrsRevisi extends Model
{
    protected $table = 'krs_revisi';

    protected $fillable = ['krs_id', 'total_sks', 'ipk_snapshot', 'rincian'];

    protected function casts(): array
    {
        return ['rincian' => 'array', 'ipk_snapshot' => 'decimal:2'];
    }

    /** @return BelongsTo<Krs, $this> */
    public function krs(): BelongsTo
    {
        return $this->belongsTo(Krs::class, 'krs_id');
    }
}
