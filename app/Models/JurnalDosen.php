<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JurnalDosen extends Model
{
    protected $table = 'jurnal_dosen';

    protected $fillable = ['pertemuan_id', 'realisasi', 'kendala'];
}
