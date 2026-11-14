<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengabdian extends Model
{
    protected $table = 'pengabdian';

    protected $fillable = ['judul', 'lokasi', 'tanggal_mulai', 'tanggal_selesai', 'ketua_dosen_id', 'dana', 'status', 'laporan_ringkas'];

    public const STATUS = ['rencana', 'berjalan', 'selesai'];

    protected function casts(): array
    {
        return ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date'];
    }

    public function ketua(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'ketua_dosen_id');
    }
}
