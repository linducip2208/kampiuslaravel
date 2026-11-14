<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeChunk extends Model
{
    protected $table = 'knowledge_chunk';

    protected $fillable = ['kb_id', 'urutan', 'teks'];

    public function dokumen(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class, 'kb_id');
    }
}
