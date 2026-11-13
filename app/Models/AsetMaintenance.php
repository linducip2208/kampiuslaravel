<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsetMaintenance extends Model
{
    protected $table = 'aset_maintenance';

    protected $fillable = ['aset_id', 'jenis', 'deskripsi', 'biaya', 'status', 'tanggal'];

    protected function casts(): array
    {
        return ['biaya' => 'integer', 'tanggal' => 'date'];
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class);
    }
}
