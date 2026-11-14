<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organisasi extends Model
{
    protected $table = 'organisasi';

    protected $fillable = ['nama', 'jenis', 'deskripsi', 'pembina_dosen_id', 'aktif'];

    public const JENIS = ['bem', 'hima', 'ukm', 'komunitas'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function pembina(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'pembina_dosen_id');
    }

    public function anggota(): HasMany
    {
        return $this->hasMany(OrganisasiAnggota::class);
    }
}
