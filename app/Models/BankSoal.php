<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankSoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'matakuliah_id', 'jenis', 'pertanyaan', 'opsi', 'kunci', 'bobot', 'aktif',
    ];

    protected function casts(): array
    {
        return ['opsi' => 'array', 'aktif' => 'boolean'];
    }

    public function matakuliah(): BelongsTo
    {
        return $this->belongsTo(Matakuliah::class, 'matakuliah_id');
    }
}
