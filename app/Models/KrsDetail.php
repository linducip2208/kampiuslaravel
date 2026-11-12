<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Detail kelas yang diambil dalam satu KRS.
 *
 * @property int $id
 * @property int $krs_id
 * @property int|null $kelas_id
 * @property int $sks
 * @property string $status
 */
class KrsDetail extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'krs_detail';

    /** @var list<string> */
    protected $fillable = [
        'krs_id',
        'kelas_id',
        'sks',
        'status',
    ];

    /**
     * @return BelongsTo<Krs, $this>
     */
    public function krs(): BelongsTo
    {
        return $this->belongsTo(Krs::class, 'krs_id');
    }

    /**
     * @return BelongsTo<Kelas, $this>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }
}
