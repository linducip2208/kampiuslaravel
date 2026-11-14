<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pemasok extends Model
{
    protected $table = 'pemasok';

    protected $fillable = ['nama', 'kontak', 'rekening', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function hutang(): HasMany
    {
        return $this->hasMany(HutangDagang::class);
    }
}
