<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifLog extends Model
{
    protected $table = 'notif_log';

    protected $fillable = ['notifikasi_id', 'user_id', 'channel', 'status', 'keterangan'];

    public function notifikasi(): BelongsTo
    {
        return $this->belongsTo(Notifikasi::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
