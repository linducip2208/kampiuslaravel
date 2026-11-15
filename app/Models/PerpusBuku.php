<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerpusBuku extends Model
{
    protected $table = 'perpus_buku';

    protected $fillable = ['kode', 'judul', 'pengarang', 'penerbit', 'tahun', 'isbn', 'kategori'];

    public function eksemplar(): HasMany
    {
        return $this->hasMany(PerpusEksemplar::class, 'buku_id');
    }

    public function tersediaCount(): int
    {
        return (int) $this->eksemplar()->where('status', PerpusEksemplar::STATUS_TERSEDIA)->count();
    }
}
