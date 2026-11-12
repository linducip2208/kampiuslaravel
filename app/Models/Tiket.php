<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tiket extends Model
{
    protected $table = 'tiket';

    protected $fillable = ['user_id', 'subjek', 'isi', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function balasan(): HasMany
    {
        return $this->hasMany(TiketBalasan::class)->oldest();
    }
}
