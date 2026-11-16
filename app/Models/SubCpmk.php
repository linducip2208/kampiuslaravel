<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubCpmk extends Model
{
    protected $table = 'sub_cpmk';

    protected $fillable = ['cpmk_id', 'kode', 'deskripsi', 'bobot'];

    protected function casts(): array
    {
        return ['bobot' => 'decimal:2'];
    }

    /** @return BelongsTo<Cpmk, $this> */
    public function cpmk(): BelongsTo
    {
        return $this->belongsTo(Cpmk::class);
    }
}
