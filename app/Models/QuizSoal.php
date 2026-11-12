<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizSoal extends Model
{
    protected $table = 'quiz_soal';

    protected $fillable = ['quiz_id', 'pertanyaan', 'tipe', 'bobot', 'kunci_jawaban'];

    protected function casts(): array
    {
        return ['bobot' => 'decimal:2'];
    }
}
