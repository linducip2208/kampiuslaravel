<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bimbingan extends Model
{
    use HasFactory;

    protected $table = 'bimbingan';

    protected $fillable = ['judul_id', 'dosen_id', 'catatan', 'file_path', 'versi', 'parent_id', 'status', 'tanggal'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'versi' => 'integer'];
    }

    public function judul(): BelongsTo
    {
        return $this->belongsTo(SkripsiJudul::class, 'judul_id');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    public function induk(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
