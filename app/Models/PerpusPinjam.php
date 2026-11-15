<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerpusPinjam extends Model
{
    protected $table = 'perpus_pinjam';

    protected $fillable = ['eksemplar_id', 'user_id', 'tgl_pinjam', 'jatuh_tempo', 'tgl_kembali', 'denda_rp', 'status'];

    public const STATUS_PINJAM = 'pinjam';

    public const STATUS_KEMBALI = 'kembali';

    public const STATUS_HILANG = 'hilang';

    protected function casts(): array
    {
        return ['tgl_pinjam' => 'date', 'jatuh_tempo' => 'date', 'tgl_kembali' => 'date'];
    }

    public function eksemplar(): BelongsTo
    {
        return $this->belongsTo(PerpusEksemplar::class, 'eksemplar_id');
    }

    public function peminjam(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
