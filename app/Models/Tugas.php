<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tugas extends Model
{
    protected $table = 'tugas';

    protected $fillable = ['kelas_id', 'pertemuan_id', 'judul', 'deskripsi', 'deadline'];

    protected function casts(): array
    {
        return ['deadline' => 'datetime'];
    }
}
