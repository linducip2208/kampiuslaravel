<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Template default tunjangan per peran (dosen/tendik/...).
 */
class TunjanganTemplate extends Model
{
    protected $table = 'tunjangan_template';

    protected $fillable = ['role', 'nominal'];

    protected function casts(): array
    {
        return ['nominal' => 'integer'];
    }
}
