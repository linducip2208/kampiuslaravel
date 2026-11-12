<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pendaftar PMB (calon mahasiswa).
 *
 * @property int $id
 * @property string $no_daftar
 * @property string $nama
 * @property string $email
 * @property string $hp
 * @property string|null $sekolah_asal
 * @property array<string, string>|null $berkas_json
 * @property string $status
 */
class PmbPendaftar extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'pmb_pendaftars';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_DIAJUKAN = 'diajukan';

    public const STATUS_DIVERIFIKASI = 'diverifikasi';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_LULUS = 'lulus';

    public const STATUS_TIDAK = 'tidak';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_DIAJUKAN,
        self::STATUS_DIVERIFIKASI,
        self::STATUS_DITOLAK,
        self::STATUS_LULUS,
        self::STATUS_TIDAK,
    ];

    /** @var list<string> */
    protected $fillable = [
        'no_daftar',
        'nama',
        'email',
        'hp',
        'sekolah_asal',
        'prodi1_id',
        'prodi2_id',
        'gelombang_id',
        'berkas_json',
        'status',
        'catatan_verifikasi',
        'user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'berkas_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<PmbGelombang, $this>
     */
    public function gelombang(): BelongsTo
    {
        return $this->belongsTo(PmbGelombang::class, 'gelombang_id');
    }

    /**
     * @return BelongsTo<Prodi, $this>
     */
    public function prodi1(): BelongsTo
    {
        return $this->belongsTo(Prodi::class, 'prodi1_id');
    }

    /**
     * @return BelongsTo<Prodi, $this>
     */
    public function prodi2(): BelongsTo
    {
        return $this->belongsTo(Prodi::class, 'prodi2_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
