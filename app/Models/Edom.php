<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Edom extends Model
{
    protected $table = 'edom';

    protected $fillable = ['mahasiswa_id', 'kelas_id', 'tahun_akademik_id', 'skor', 'kritik'];
}
