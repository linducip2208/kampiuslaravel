<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TugasKumpul extends Model
{
    protected $table = 'tugas_kumpul';

    protected $fillable = ['tugas_id', 'mahasiswa_id', 'file_path', 'is_late', 'nilai'];

    protected function casts(): array
    {
        return ['nilai' => 'decimal:2', 'is_late' => 'boolean'];
    }
}
