<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WisudaPeserta extends Model
{
    protected $table = 'wisuda_peserta';

    protected $fillable = ['batch_id', 'mahasiswa_id', 'no_kursi', 'status_toga', 'no_ijazah', 'pin_ijazah', 'status_skl'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(WisudaBatch::class, 'batch_id');
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
