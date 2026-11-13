<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pembayaran extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pembayaran';

    protected $fillable = ['tagihan_id', 'reference', 'va_number', 'qr_payload', 'expires_at', 'metode', 'nominal', 'bukti_path', 'status', 'verified_by', 'verified_at', 'refunded_at', 'refund_reason', 'reconciled_at'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime', 'expires_at' => 'datetime', 'refunded_at' => 'datetime', 'reconciled_at' => 'datetime'];
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }
}
