<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiKuota extends Model
{
    protected $table = 'ai_kuota';

    protected $fillable = ['user_id', 'tanggal', 'jenis', 'jumlah', 'estimasi_biaya'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
