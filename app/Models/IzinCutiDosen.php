<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IzinCutiDosen extends Model
{
    protected $table = 'izin_cuti_dosen';

    protected $fillable = ['dosen_id', 'jenis', 'tgl_mulai', 'tgl_selesai', 'alasan', 'status'];

    protected function casts(): array
    {
        return ['tgl_mulai' => 'date', 'tgl_selesai' => 'date'];
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    public function isOverlapping(string $mulai, string $selesai, ?int $ignoreId = null): bool
    {
        return static::where('dosen_id', $this->dosen_id)
            ->where('status', '!=', 'ditolak')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('tgl_mulai', '<=', $selesai)
            ->where('tgl_selesai', '>=', $mulai)
            ->exists();
    }
}
