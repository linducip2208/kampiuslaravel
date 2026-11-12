<?php

namespace App\Models;

use Database\Factories\RuangFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ruang extends Model
{
    /** @use HasFactory<RuangFactory> */
    use HasFactory;

    protected $table = 'ruang';

    protected $fillable = ['gedung', 'nama', 'kapasitas'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kapasitas' => 'integer',
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
