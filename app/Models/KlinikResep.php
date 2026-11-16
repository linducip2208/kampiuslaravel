<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Resep obat per kunjungan klinik.
 */
class KlinikResep extends Model
{
    protected $table = 'klinik_resep';

    protected $fillable = ['kunjungan_id', 'obat', 'dosis', 'jumlah'];

    /** @return BelongsTo<KlinikKunjungan, $this> */
    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(KlinikKunjungan::class, 'kunjungan_id');
    }
}
