<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DelegasiAkses extends Model
{
    protected $table = 'delegasi_akses';

    protected $fillable = [
        'dari_user_id', 'ke_user_id', 'role', 'mulai_at', 'sampai_at',
        'status', 'keperluan', 'diterapkan_pada',
    ];

    protected function casts(): array
    {
        return ['mulai_at' => 'date', 'sampai_at' => 'date', 'diterapkan_pada' => 'datetime'];
    }

    public function dari(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dari_user_id');
    }

    public function ke(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ke_user_id');
    }

    public function aktifHariIni(): bool
    {
        if ($this->status !== 'aktif') {
            return false;
        }

        $hariIni = today()->toDateString();

        return $this->mulai_at->toDateString() <= $hariIni && $hariIni <= $this->sampai_at->toDateString();
    }
}
