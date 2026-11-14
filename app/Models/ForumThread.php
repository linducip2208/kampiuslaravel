<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumThread extends Model
{
    protected $table = 'forum_thread';

    protected $fillable = ['kelas_id', 'dibuat_oleh', 'judul', 'isi', 'terkunci', 'disematkan'];

    protected function casts(): array
    {
        return ['terkunci' => 'boolean', 'disematkan' => 'boolean'];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function penulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function balasan(): HasMany
    {
        return $this->hasMany(ForumBalasan::class, 'thread_id');
    }
}
