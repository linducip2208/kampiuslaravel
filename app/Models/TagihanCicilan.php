<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TagihanCicilan extends Model
{
    protected $table = 'tagihan_cicilan';

    protected $fillable = ['tagihan_id', 'urutan', 'nominal', 'terbayar', 'jatuh_tempo', 'status'];

    protected function casts(): array
    {
        return ['jatuh_tempo' => 'date'];
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function sisa(): int
    {
        return max(0, (int) $this->nominal - (int) $this->terbayar);
    }
}
