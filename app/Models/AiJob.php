<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiJob extends Model
{
    protected $fillable = ['user_id', 'jenis', 'input', 'output', 'status', 'provider', 'error'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
