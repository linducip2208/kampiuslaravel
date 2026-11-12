<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $table = 'quiz';

    protected $fillable = ['kelas_id', 'judul', 'durasi_menit', 'deadline'];

    protected function casts(): array
    {
        return ['deadline' => 'datetime'];
    }
}
