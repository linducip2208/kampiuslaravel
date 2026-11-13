<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WisudaBatch extends Model
{
    protected $table = 'wisuda_batch';

    protected $fillable = ['nama', 'periode', 'tanggal_wisuda', 'lokasi', 'kuota', 'status'];

    protected $casts = ['tanggal_wisuda' => 'date'];

    public function peserta(): HasMany
    {
        return $this->hasMany(WisudaPeserta::class, 'batch_id');
    }
}
