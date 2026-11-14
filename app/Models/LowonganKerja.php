<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LowonganKerja extends Model
{
    protected $table = 'lowongan_kerja';

    protected $fillable = ['judul', 'unit', 'jenis', 'deskripsi', 'kuota', 'tgl_tutup', 'status'];

    public const JENIS = ['dosen', 'tendik'];

    public const STATUS = ['buka', 'tutup'];

    protected function casts(): array
    {
        return ['tgl_tutup' => 'date'];
    }

    public function lamaran(): HasMany
    {
        return $this->hasMany(LamaranKerja::class, 'lowongan_id');
    }

    public function masihBuka(): bool
    {
        return $this->status === 'buka'
            && ($this->tgl_tutup === null || $this->tgl_tutup->isFuture() || $this->tgl_tutup->isToday());
    }
}
