<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizJawaban extends Model
{
    protected $table = 'quiz_jawaban';

    protected $fillable = ['quiz_soal_id', 'mahasiswa_id', 'jawaban', 'skor'];

    protected function casts(): array
    {
        return ['skor' => 'decimal:2'];
    }
}
