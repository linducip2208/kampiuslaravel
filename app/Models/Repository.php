<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Payload indeks full-text yang kompatibel dengan Meilisearch/Scout.
     *
     * @return array{judul: string, abstrak: string, tahun: ?string}
     */
    public function toSearchableArray(): array
    {
        return [
            'judul' => (string) $this->judul,
            'abstrak' => (string) ($this->abstrak ?? ''),
            'tahun' => $this->tahun,
        ];
    }

    public function searchableAs(): string
    {
        return 'repository_index';
    }

    /**
     * @param  Builder<Repository>  $query
     */
    public function scopePencarian($query, string $keyword)
    {
        $keyword = trim($keyword);

        return $query->when($keyword !== '', function ($q) use ($keyword) {
            $q->where(function ($w) use ($keyword) {
                $w->where('judul', 'like', '%'.$keyword.'%')
                    ->orWhere('abstrak', 'like', '%'.$keyword.'%')
                    ->orWhere('tahun', 'like', '%'.$keyword.'%');
            });
        });
    }
}
