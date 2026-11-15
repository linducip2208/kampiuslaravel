<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditBaca extends Model
{
    protected $table = 'audit_baca';

    protected $fillable = ['user_id', 'model', 'model_id', 'dibaca_at', 'ip'];

    protected function casts(): array
    {
        return ['dibaca_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
