<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Checklist onboarding per lamaran yang diterima.
 */
class OnboardingChecklist extends Model
{
    protected $table = 'onboarding_checklist';

    public const ITEM_DEFAULT = ['Buat akun pengguna', 'Terbitkan SK pengangkatan', 'Orientasi unit kerja'];

    protected $fillable = ['lamaran_id', 'item', 'selesai'];

    protected function casts(): array
    {
        return ['selesai' => 'boolean'];
    }

    /** @return BelongsTo<LamaranKerja, $this> */
    public function lamaran(): BelongsTo
    {
        return $this->belongsTo(LamaranKerja::class, 'lamaran_id');
    }
}
