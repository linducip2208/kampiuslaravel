<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tautan akun orangtua/wali ke mahasiswa untuk API read-only.
 */
class WaliMahasiswa extends Model
{
    protected $table = 'wali_mahasiswa';

    protected $fillable = ['user_id', 'mahasiswa_id'];

    /** @return BelongsTo<Mahasiswa, $this> */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    /** @return BelongsTo<User, $this> */
    public function wali(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
