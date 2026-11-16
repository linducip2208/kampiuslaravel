<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeBase extends Model
{
    protected $table = 'knowledge_base';

    protected $fillable = ['judul', 'sumber', 'konten', 'aktif', 'prodi_id'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function chunk(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'kb_id')->orderBy('urutan');
    }
}
