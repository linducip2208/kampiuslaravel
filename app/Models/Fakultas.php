<?php

namespace App\Models;

use Database\Factories\FakultasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fakultas extends Model
{
    /** @use HasFactory<FakultasFactory> */
    use HasFactory;

    protected $table = 'fakultas';

    protected $fillable = ['nama', 'kode'];

    /**
     * @return HasMany<Prodi, $this>
     */
    public function prodi(): HasMany
    {
        return $this->hasMany(Prodi::class);
    }
}
