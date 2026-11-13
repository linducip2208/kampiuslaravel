<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alumni extends Model
{
    protected $table = 'alumni';

    protected $fillable = ['mahasiswa_id', 'tahun_lulus', 'pekerjaan', 'instansi', 'hp', 'bersedia_tracer'];

    protected $casts = ['bersedia_tracer' => 'boolean'];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
