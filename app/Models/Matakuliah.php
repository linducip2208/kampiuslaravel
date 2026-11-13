<?php

namespace App\Models;

use Database\Factories\MatakuliahFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matakuliah extends Model
{
    /** @use HasFactory<MatakuliahFactory> */
    use HasFactory;

    protected $table = 'matakuliah';

    protected $fillable = ['prodi_id', 'kode', 'nama', 'sks', 'semester', 'prasyarat_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sks' => 'integer',
            'semester' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Prodi, $this>
     */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    /**
     * @return BelongsTo<Matakuliah, $this>
     */
    public function prasyarat(): BelongsTo
    {
        return $this->belongsTo(Matakuliah::class, 'prasyarat_id');
    }

    /**
     * @return HasMany<Kelas, $this>
     */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    /**
     * Graf prasyarat baru (banyak-ke-banyak, berjenis).
     *
     * @return HasMany<MkPrasyarat, $this>
     */
    public function syarat(): HasMany
    {
        return $this->hasMany(MkPrasyarat::class);
    }

    /**
     * @return BelongsToMany<Matakuliah, $this>
     */
    public function prasyaratBanyak(): BelongsToMany
    {
        return $this->belongsToMany(Matakuliah::class, 'mk_prasyarat', 'matakuliah_id', 'prasyarat_id')
            ->withPivot(['jenis', 'keterangan']);
    }

    /**
     * @return BelongsToMany<Kurikulum, $this>
     */
    public function kurikulums(): BelongsToMany
    {
        return $this->belongsToMany(Kurikulum::class, 'kurikulum_mk')
            ->withPivot(['semester', 'sifat'])
            ->withTimestamps();
    }
}
