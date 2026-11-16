<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Ide bank: envelope paging konsisten untuk API v1 —
 * `?q=&sort=&per_page=` + `{data, meta}`.
 */
class ApiPaging
{
    /**
     * @param  list<string>  $sorts  kolom yang boleh di-sort (boleh prefix `-` untuk desc)
     */
    public static function paginate(Builder $query, Request $request, array $sorts, string $defaultSort = '-id'): LengthAwarePaginator
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', 'max:30'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $sort = (string) ($data['sort'] ?? $defaultSort);
        $desc = str_starts_with($sort, '-');
        $kolom = ltrim($sort, '-');

        if (! in_array($kolom, $sorts, true)) {
            $kolom = ltrim($defaultSort, '-');
            $desc = str_starts_with($defaultSort, '-');
        }

        return $query
            ->when($desc, fn ($q) => $q->orderByDesc($kolom), fn ($q) => $q->orderBy($kolom))
            ->paginate($data['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * @return array{current_page: int, last_page: int, per_page: int, total: int}
     */
    public static function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    public static function search(Builder $query, ?string $q, string ...$koloms): Builder
    {
        if ($q === null || trim($q) === '' || $koloms === []) {
            return $query;
        }

        $kata = '%'.str_replace(['%', '_'], '', trim($q)).'%';

        return $query->where(function ($w) use ($koloms, $kata): void {
            foreach ($koloms as $i => $kolom) {
                $i === 0 ? $w->where($kolom, 'like', $kata) : $w->orWhere($kolom, 'like', $kata);
            }
        });
    }
}
