<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KalenderEvent extends Model
{
    protected $table = 'kalender_events';

    protected $fillable = ['tahun_akademik_id', 'judul', 'jenis', 'tgl_mulai', 'tgl_selesai', 'deskripsi'];

    protected function casts(): array
    {
        return ['tgl_mulai' => 'date', 'tgl_selesai' => 'date'];
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class);
    }
}
