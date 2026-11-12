<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pertemuan extends Model
{
    protected $table = 'pertemuan';

    protected $fillable = [
        'kelas_id', 'ke', 'tanggal', 'topik',
        'qr_token', 'qr_expires_at', 'window_mulai', 'window_selesai', 'pin',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'qr_expires_at' => 'datetime',
            'window_mulai' => 'datetime',
            'window_selesai' => 'datetime',
        ];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class);
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }
}
