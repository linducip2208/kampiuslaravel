<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CpmkAsesmen extends Model
{
    protected $table = 'cpmk_asesmen';

    protected $fillable = ['cpmk_id', 'sumber_tipe', 'sumber_id', 'bobot'];

    public const SUMBER_QUIZ = 'quiz_soal';

    public const SUMBER_TUGAS = 'tugas';

    protected function casts(): array
    {
        return ['bobot' => 'decimal:2'];
    }

    public function cpmk(): BelongsTo
    {
        return $this->belongsTo(Cpmk::class, 'cpmk_id');
    }
}
