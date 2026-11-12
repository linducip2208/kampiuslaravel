<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SidangPenguji extends Model
{
    use HasFactory;

    protected $table = 'sidang_penguji';

    protected $fillable = ['sidang_id', 'dosen_id', 'peran', 'nilai'];

    protected function casts(): array
    {
        return ['nilai' => 'decimal:2'];
    }

    public function sidang(): BelongsTo
    {
        return $this->belongsTo(Sidang::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }
}
