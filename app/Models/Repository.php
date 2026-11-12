<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repository extends Model
{
    use HasFactory;

    protected $table = 'repository';

    protected $fillable = ['mahasiswa_id', 'judul', 'abstrak', 'file_path', 'tahun', 'embargo'];

    protected function casts(): array
    {
        return ['embargo' => 'boolean'];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
