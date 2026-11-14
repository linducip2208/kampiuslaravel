<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = ['sumber', 'event', 'idempotency_key', 'payload_json'];

    protected function casts(): array
    {
        return ['payload_json' => 'array'];
    }
}
