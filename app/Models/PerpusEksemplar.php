<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerpusEksemplar extends Model
{
    protected $table = 'perpus_eksemplar';

    protected $fillable = ['buku_id', 'barcode', 'status', 'kondisi'];

    public const STATUS_TERSEDIA = 'tersedia';

    public const STATUS_DIPINJAM = 'dipinjam';

    public const STATUS_HILANG = 'hilang';

    public const STATUS_RUSAK = 'rusak';

    public function buku(): BelongsTo
    {
        return $this->belongsTo(PerpusBuku::class, 'buku_id');
    }
}
