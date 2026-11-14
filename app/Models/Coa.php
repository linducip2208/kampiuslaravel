<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coa extends Model
{
    protected $table = 'coa';

    protected $fillable = ['kode', 'nama', 'tipe', 'normal', 'aktif'];

    public const TIPE = ['aset', 'kewajiban', 'ekuitas', 'pendapatan', 'beban'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }
}
