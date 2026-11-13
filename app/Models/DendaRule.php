<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DendaRule extends Model
{
    protected $table = 'denda_rules';

    protected $fillable = ['tahun_akademik_id', 'jenis', 'terlambat_hari_min', 'mode', 'nilai', 'maksimal', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function hitungDenda(int $nominal, int $hariTerlambat): int
    {
        if (! $this->aktif || $hariTerlambat < (int) $this->terlambat_hari_min) {
            return 0;
        }

        $denda = $this->mode === 'persen'
            ? (int) round($nominal * min(100, (int) $this->nilai) / 100)
            : min($nominal, (int) $this->nilai);

        if ($this->maksimal !== null) {
            $denda = min($denda, (int) $this->maksimal);
        }

        return $denda;
    }
}
