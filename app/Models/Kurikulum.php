<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kurikulum extends Model
{
    protected $table = 'kurikulum';

    protected $fillable = ['prodi_id', 'kode', 'nama', 'tahun_berlaku', 'status', 'keterangan'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun_berlaku' => 'integer',
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
     * @return HasMany<KurikulumMk, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(KurikulumMk::class);
    }

    /**
     * @return BelongsToMany<Matakuliah, $this>
     */
    public function matakuliah(): BelongsToMany
    {
        return $this->belongsToMany(Matakuliah::class, 'kurikulum_mk')
            ->withPivot(['semester', 'sifat'])
            ->withTimestamps();
    }
}
