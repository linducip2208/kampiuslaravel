<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HutangDagang extends Model
{
    protected $table = 'hutang_dagang';

    protected $fillable = ['pemasok_id', 'nomor', 'uraian', 'nominal', 'terbayar', 'jatuh_tempo', 'status'];

    public const STATUS_BELUM = 'belum_bayar';

    public const STATUS_SEBAGIAN = 'sebagian';

    public const STATUS_LUNAS = 'lunas';

    protected function casts(): array
    {
        return ['jatuh_tempo' => 'date'];
    }

    public function pemasok(): BelongsTo
    {
        return $this->belongsTo(Pemasok::class);
    }

    public function sisa(): int
    {
        return max(0, (int) $this->nominal - (int) $this->terbayar);
    }
}
