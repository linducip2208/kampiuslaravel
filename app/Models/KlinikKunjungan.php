<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rekam medis sederhana klinik kampus (data sensitif — gate klinik.*).
 */
class KlinikKunjungan extends Model
{
    protected $table = 'klinik_kunjungan';

    protected $fillable = ['mahasiswa_id', 'tanggal', 'keluhan', 'diagnosa', 'tindakan', 'pemeriksa_id'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    /** @return BelongsTo<Mahasiswa, $this> */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    /** @return HasMany<KlinikResep, $this> */
    public function resep(): HasMany
    {
        return $this->hasMany(KlinikResep::class, 'kunjungan_id');
    }
}
