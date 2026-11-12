<?php

namespace App\Models;

use Database\Factories\TahunAkademikFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAkademik extends Model
{
    /** @use HasFactory<TahunAkademikFactory> */
    use HasFactory;

    protected $table = 'tahun_akademik';

    protected $fillable = ['tahun', 'semester', 'aktif'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Kelas, $this>
     */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }
}
