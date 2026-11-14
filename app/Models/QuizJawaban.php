<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizJawaban extends Model
{
    protected $table = 'quiz_jawaban';

    protected $fillable = ['quiz_soal_id', 'mahasiswa_id', 'jawaban', 'skor'];

    protected function casts(): array
    {
        return ['skor' => 'decimal:2'];
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(QuizSoal::class, 'quiz_soal_id');
    }
}
