<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KurikulumMk extends Model
{
    protected $table = 'kurikulum_mk';

    protected $fillable = ['kurikulum_id', 'matakuliah_id', 'semester', 'sifat'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semester' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Kurikulum, $this>
     */
    public function kurikulum(): BelongsTo
    {
        return $this->belongsTo(Kurikulum::class);
    }

    /**
     * @return BelongsTo<Matakuliah, $this>
     */
    public function matakuliah(): BelongsTo
    {
        return $this->belongsTo(Matakuliah::class);
    }
}
