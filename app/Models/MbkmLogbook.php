<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MbkmLogbook extends Model
{
    protected $table = 'mbkm_logbook';

    protected $fillable = ['konversi_id', 'tanggal', 'aktivitas', 'bukti_path', 'status'];

    protected $casts = ['tanggal' => 'date'];

    public function konversi(): BelongsTo
    {
        return $this->belongsTo(MbkmKonversi::class, 'konversi_id');
    }
}
