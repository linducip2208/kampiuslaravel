<?php

namespace App\Models;

use Database\Factories\MatakuliahFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
