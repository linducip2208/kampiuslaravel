<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beasiswa extends Model
{
    use HasFactory;

    protected $table = 'beasiswa';

    protected $fillable = ['nama', 'jenis', 'nilai', 'kuota', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function hitungPotongan(int $nominal): int
    {
        if ($this->jenis === 'persen') {
            return (int) round($nominal * min(100, (int) $this->nilai) / 100);
        }

        return min($nominal, (int) $this->nilai);
    }
}
