<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpdeskSla extends Model
{
    protected $table = 'helpdesk_sla';

    protected $fillable = ['kategori', 'prioritas', 'respon_jam', 'resolusi_jam', 'eskalasi_role'];

    protected function casts(): array
    {
        return ['respon_jam' => 'integer', 'resolusi_jam' => 'integer'];
    }

    public static function target(string $kategori, string $prioritas): ?self
    {
        return static::where('kategori', $kategori)->where('prioritas', $prioritas)->first()
            ?? static::where('kategori', 'umum')->where('prioritas', $prioritas)->first()
            ?? static::where('kategori', 'umum')->where('prioritas', 'normal')->first();
    }
}
