<?php

namespace App\Models;

use Database\Factories\ProdiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prodi extends Model
{
    /** @use HasFactory<ProdiFactory> */
    use HasFactory;

    protected $table = 'prodi';

    protected $fillable = ['fakultas_id', 'nama', 'jenjang', 'kode', 'slug'];

    /**
     * @return BelongsTo<Fakultas, $this>
     */
    public function fakultas(): BelongsTo
    {
        return $this->belongsTo(Fakultas::class);
    }

    /**
     * @return HasMany<Mahasiswa, $this>
     */
    public function mahasiswa(): HasMany
    {
        return $this->hasMany(Mahasiswa::class);
    }

    /**
     * @return HasMany<Dosen, $this>
     */
    public function dosen(): HasMany
    {
        return $this->hasMany(Dosen::class);
    }

    /**
     * @return HasMany<Matakuliah, $this>
     */
    public function matakuliah(): HasMany
    {
        return $this->hasMany(Matakuliah::class);
    }
}
