<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LamaranKerja extends Model
{
    protected $table = 'lamaran_kerja';

    protected $fillable = ['lowongan_id', 'nama', 'email', 'hp', 'berkas_path', 'status'];

    public const STATUS = ['masuk', 'seleksi', 'diterima', 'ditolak'];

    public function lowongan(): BelongsTo
    {
        return $this->belongsTo(LowonganKerja::class, 'lowongan_id');
    }

    /** @return HasMany<OnboardingChecklist, $this> */
    public function onboarding(): HasMany
    {
        return $this->hasMany(OnboardingChecklist::class, 'lamaran_id');
    }
}
