<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hunian aktif asrama: 1 mahasiswa = 1 kamar (unique).
 */
class AsramaHuni extends Model
{
    protected $table = 'asrama_huni';

    protected $fillable = ['kamar_id', 'mahasiswa_id', 'masuk_at'];

    protected function casts(): array
    {
        return ['masuk_at' => 'date'];
    }

    /** @return BelongsTo<AsramaKamar, $this> */
    public function kamar(): BelongsTo
    {
        return $this->belongsTo(AsramaKamar::class, 'kamar_id');
    }

    /** @return BelongsTo<Mahasiswa, $this> */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
