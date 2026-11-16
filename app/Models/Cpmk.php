<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cpmk extends Model
{
    protected $table = 'cpmk';

    protected $fillable = ['matakuliah_id', 'cpl_id', 'kode', 'deskripsi', 'bobot'];

    protected function casts(): array
    {
        return ['bobot' => 'decimal:2'];
    }

    public function matakuliah(): BelongsTo
    {
        return $this->belongsTo(Matakuliah::class);
    }

    public function cpl(): BelongsTo
    {
        return $this->belongsTo(Cpl::class, 'cpl_id');
    }

    public function asesmen(): HasMany
    {
        return $this->hasMany(CpmkAsesmen::class, 'cpmk_id');
    }

    public function subCpmk(): HasMany
    {
        return $this->hasMany(SubCpmk::class, 'cpmk_id');
    }
}
