<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MkPrasyarat extends Model
{
    protected $table = 'mk_prasyarat';

    public const JENIS_PRASYARAT = 'prasyarat';

    public const JENIS_EKIVALENSI = 'ekivalensi';

    protected $fillable = ['matakuliah_id', 'prasyarat_id', 'jenis', 'keterangan'];

    /**
     * @return BelongsTo<Matakuliah, $this>
     */
    public function matakuliah(): BelongsTo
    {
        return $this->belongsTo(Matakuliah::class);
    }

    /**
     * @return BelongsTo<Matakuliah, $this>
     */
    public function prasyarat(): BelongsTo
    {
        return $this->belongsTo(Matakuliah::class, 'prasyarat_id');
    }
}
