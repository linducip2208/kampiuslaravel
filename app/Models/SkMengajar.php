<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkMengajar extends Model
{
    protected $table = 'sk_mengajar';

    protected $fillable = ['dosen_id', 'tahun_akademik_id', 'nomor', 'tanggal_terbit', 'total_sks', 'daftar_kelas'];

    protected function casts(): array
    {
        return ['tanggal_terbit' => 'date', 'total_sks' => 'integer'];
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
