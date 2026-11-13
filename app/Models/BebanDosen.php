<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BebanDosen extends Model
{
    protected $table = 'beban_dosen';

    protected $fillable = ['dosen_id', 'tahun_akademik_id', 'kategori', 'uraian', 'sks', 'bukti', 'status'];

    protected function casts(): array
    {
        return ['sks' => 'integer'];
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class);
    }
}
