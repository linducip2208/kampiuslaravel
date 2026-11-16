<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Siklus PPEMPMTI: penetapan → pelaksanaan → evaluasi →
 * pengendalian → peningkatan, status per tahap.
 */
class SpmiSiklus extends Model
{
    protected $table = 'spmi_siklus';

    public const TAHAP = ['penetapan', 'pelaksanaan', 'evaluasi', 'pengendalian', 'peningkatan'];

    public const STATUS = ['belum', 'sedang', 'selesai'];

    protected $fillable = ['prodi_id', 'tahun', 'tahap', 'status', 'catatan'];

    protected function casts(): array
    {
        return ['tahun' => 'integer'];
    }

    /** @return BelongsTo<Prodi, $this> */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }
}
