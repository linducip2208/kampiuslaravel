<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SidangPenguji extends Model
{
    use HasFactory;

    protected $table = 'sidang_penguji';

    protected $fillable = ['sidang_id', 'dosen_id', 'nama_eksternal', 'peran', 'nilai'];

    /** Nama tampil: dosen internal atau nama eksternal. */
    public function namaTampil(): string
    {
        return (string) ($this->nama_eksternal ?? $this->dosen->user->name ?? $this->dosen->nidn ?? "Dosen #{$this->dosen_id}");
    }

    protected function casts(): array
    {
        return ['nilai' => 'decimal:2'];
    }

    public function sidang(): BelongsTo
    {
        return $this->belongsTo(Sidang::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }
}
