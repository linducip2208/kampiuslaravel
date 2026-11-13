<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalKeuangan extends Model
{
    use HasFactory;

    protected $table = 'jurnal_keuangan';

    protected $fillable = [
        'tanggal', 'pembayaran_id', 'tagihan_id', 'jenis',
        'akun_debet', 'akun_kredit', 'nominal', 'keterangan', 'created_by',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class);
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }
}
