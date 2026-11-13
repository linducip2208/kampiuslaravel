<?php

namespace App\Services;

use App\Models\Repository;
use Illuminate\Support\Collection;

class RepositorySearchService
{
    /**
     * Full-text search repositori. Memakai Meilisearch/Scout bila tersedia,
     * fallback ke LIKE agar tetap jalan tanpa dependensi tambahan.
     *
     * @return Collection<int, Repository>
     */
    public static function search(string $keyword, int $limit = 20): Collection
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return Repository::query()->latest()->take($limit)->get();
        }

        if (self::meilisearchTersedia()) {
            /** @var Collection<int, Repository> $hasil */
            $hasil = Repository::search($keyword)->take($limit)->get();
            if ($hasil->isNotEmpty()) {
                return $hasil;
            }
        }

        return Repository::query()->pencarian($keyword)->latest()->take($limit)->get();
    }

    public static function meilisearchTersedia(): bool
    {
        return (bool) config('scout.driver')
            && in_array('Laravel\Scout\Searchable', class_uses_recursive(Repository::class), true);
    }
}
