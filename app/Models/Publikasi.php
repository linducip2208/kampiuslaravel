<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Publikasi extends Model
{
    protected $table = 'publikasi';

    protected $fillable = ['judul', 'jenis', 'penulis', 'dosen_id', 'penerbit', 'tahun', 'doi', 'status'];

    public const JENIS = ['jurnal', 'prosiding', 'buku', 'hki'];

    public const STATUS = ['draft', 'diajukan', 'terbit'];

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }
}
