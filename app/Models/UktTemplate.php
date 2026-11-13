<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UktTemplate extends Model
{
    protected $table = 'ukt_templates';

    protected $fillable = ['prodi_id', 'angkatan', 'jalur', 'jenis', 'nominal', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }
}
