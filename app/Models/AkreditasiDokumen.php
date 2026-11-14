<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AkreditasiDokumen extends Model
{
    protected $table = 'akreditasi_dokumen';

    protected $fillable = ['prodi_id', 'kategori', 'judul', 'tautan', 'file_path', 'status', 'diverifikasi_oleh', 'diverifikasi_at'];

    public const STATUS = ['draft', 'diajukan', 'terverifikasi'];

    protected function casts(): array
    {
        return ['diverifikasi_at' => 'datetime'];
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }
}
