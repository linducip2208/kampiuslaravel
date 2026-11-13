<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Antrean mahasiswa saat kuota kelas penuh.
 *
 * @property int $id
 * @property int $kelas_id
 * @property int $mahasiswa_id
 * @property string $status
 */
class KelasWaitlist extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'kelas_waitlist';

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DIPROMOSIKAN = 'dipromosikan';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    /** @var list<string> */
    protected $fillable = [
        'kelas_id',
        'mahasiswa_id',
        'status',
    ];

    /**
     * @return BelongsTo<Kelas, $this>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    /**
     * @return BelongsTo<Mahasiswa, $this>
     */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }
}
