<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Gelombang PMB (penerimaan mahasiswa baru).
 *
 * @property int $id
 * @property string $nama
 * @property int|null $tahun_akademik_id
 * @property string|null $tgl_buka
 * @property string|null $tgl_tutup
 * @property int $kuota
 * @property int $biaya
 * @property bool $aktif
 */
class PmbGelombang extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'pmb_gelombangs';

    /** @var list<string> */
    protected $fillable = [
        'nama',
        'tahun_akademik_id',
        'tgl_buka',
        'tgl_tutup',
        'kuota',
        'biaya',
        'aktif',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tgl_buka' => 'date',
            'tgl_tutup' => 'date',
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<PmbPendaftar, $this>
     */
    public function pendaftars(): HasMany
    {
        return $this->hasMany(PmbPendaftar::class, 'gelombang_id');
    }

    /**
     * @return BelongsTo<TahunAkademik, $this>
     */
    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    public function isBuka(): bool
    {
        if (! $this->aktif) {
            return false;
        }

        $today = today();

        if ($this->tgl_buka && $today->lt($this->tgl_buka)) {
            return false;
        }

        if ($this->tgl_tutup && $today->gt($this->tgl_tutup)) {
            return false;
        }

        return true;
    }

    public function terisi(): int
    {
        return $this->pendaftars()
            ->whereNotIn('status', [PmbPendaftar::STATUS_DITOLAK, PmbPendaftar::STATUS_TIDAK])
            ->count();
    }

    public function lulusCount(): int
    {
        return $this->pendaftars()->where('status', PmbPendaftar::STATUS_LULUS)->count();
    }

    public function sisaKuota(): ?int
    {
        if ($this->kuota <= 0) {
            return null;
        }

        return max(0, $this->kuota - $this->terisi());
    }

    public function kuotaPenuh(): bool
    {
        return $this->kuota > 0 && $this->terisi() >= $this->kuota;
    }

    public function kuotaLulusPenuh(): bool
    {
        return $this->kuota > 0 && $this->lulusCount() >= $this->kuota;
    }
}
