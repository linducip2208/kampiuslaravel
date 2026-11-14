<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalUmum extends Model
{
    protected $table = 'jurnal_umum';

    protected $fillable = ['tanggal', 'tahun_akademik_id', 'keterangan', 'referensi', 'dibuat_oleh'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function item(): HasMany
    {
        return $this->hasMany(JurnalItem::class, 'jurnal_id');
    }

    public function totalDebet(): int
    {
        return (int) $this->item()->sum('debet');
    }

    public function totalKredit(): int
    {
        return (int) $this->item()->sum('kredit');
    }
}
