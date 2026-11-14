<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HibahPencairan extends Model
{
    protected $table = 'hibah_pencairan';

    protected $fillable = ['hibah_id', 'tahap', 'nominal', 'tanggal', 'keterangan'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function hibah(): BelongsTo
    {
        return $this->belongsTo(HibahRiset::class, 'hibah_id');
    }
}
