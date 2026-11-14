<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HibahRiset extends Model
{
    protected $table = 'hibah_riset';

    protected $fillable = ['judul', 'skema', 'sumber_dana', 'tahun', 'total_dana', 'ketua_dosen_id', 'status'];

    public const STATUS = ['diajukan', 'berjalan', 'selesai', 'ditolak'];

    public function ketua(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'ketua_dosen_id');
    }

    public function pencairan(): HasMany
    {
        return $this->hasMany(HibahPencairan::class, 'hibah_id');
    }

    public function totalCair(): int
    {
        try {
            return (int) $this->pencairan()->sum('nominal');
        } catch (\Throwable) {
            return 0;
        }
    }
}
