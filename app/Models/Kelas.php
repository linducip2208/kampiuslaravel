<?php

namespace App\Models;

use Database\Factories\KelasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kelas extends Model
{
    /** @use HasFactory<KelasFactory> */
    use HasFactory;

    protected $table = 'kelas';

    protected $fillable = [
        'matakuliah_id',
        'tahun_akademik_id',
        'nama',
        'kuota',
        'dosen_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'ruang_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kuota' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Matakuliah, $this>
     */
    public function matakuliah(): BelongsTo
    {
        return $this->belongsTo(Matakuliah::class);
    }

    /**
     * @return BelongsTo<TahunAkademik, $this>
     */
    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class);
    }

    /**
     * @return BelongsTo<Dosen, $this>
     */
    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    /**
     * @return BelongsTo<Ruang, $this>
     */
    public function ruang(): BelongsTo
    {
        return $this->belongsTo(Ruang::class);
    }
}
