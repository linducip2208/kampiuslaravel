<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kamar asrama: kapasitas = jumlah bed.
 */
class AsramaKamar extends Model
{
    protected $table = 'asrama_kamar';

    protected $fillable = ['gedung', 'kode', 'kapasitas', 'iuran'];

    protected function casts(): array
    {
        return ['kapasitas' => 'integer', 'iuran' => 'integer'];
    }

    /** @return HasMany<AsramaHuni, $this> */
    public function penghuni(): HasMany
    {
        return $this->hasMany(AsramaHuni::class, 'kamar_id');
    }

    public function terisi(): int
    {
        return $this->penghuni()->count();
    }

    public function penuh(): bool
    {
        return $this->terisi() >= (int) $this->kapasitas;
    }
}
