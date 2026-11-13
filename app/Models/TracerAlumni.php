<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TracerAlumni extends Model
{
    protected $table = 'tracer_alumni';

    protected $fillable = ['mahasiswa_id', 'tahun_lulus', 'status_kerja', 'perusahaan', 'jabatan', 'rentang_gaji', 'masukan'];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
