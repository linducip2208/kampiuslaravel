<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cpl extends Model
{
    protected $table = 'cpl';

    protected $fillable = ['prodi_id', 'kode', 'deskripsi'];

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function cpmk(): HasMany
    {
        return $this->hasMany(Cpmk::class, 'cpl_id');
    }
}
