<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    protected $table = 'quiz';

    protected $fillable = ['kelas_id', 'judul', 'durasi_menit', 'deadline'];

    protected function casts(): array
    {
        return ['deadline' => 'datetime'];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function soal(): HasMany
    {
        return $this->hasMany(QuizSoal::class, 'quiz_id');
    }

    public function attempt(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'quiz_id');
    }
}
