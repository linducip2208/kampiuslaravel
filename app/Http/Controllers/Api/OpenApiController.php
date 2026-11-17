<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

/**
 * M-045: dokumentasi OpenAPI 3.1 yang digenerate dari rute `api/*`
 * terdaftar (tanpa dependensi baru). Kontrak: selalu ada `openapi`,
 * `info`, `paths`; tiap path operasi GET/POST/PUT/PATCH/DELETE.
 */
class OpenApiController extends Controller
{
    /**
     * Koleksi GET yang memakai App\Support\ApiPaging (`?q=&sort=&per_page=`
     * + envelope `{data, meta}`). Kunci = path hasil generate di bawah.
     *
     * @var array<string, list<string>>
     */
    private const PAGED_SORTS = [
        '/api/v1/tugas' => ['id', 'judul', 'deadline'],
        '/api/v1/tagihan' => ['id', 'nominal', 'jatuh_tempo'],
        '/api/v1/pengumuman' => ['id', 'published_at'],
        '/api/v2/pengumuman' => ['id', 'published_at'],
    ];

    public function show(): JsonResponse
    {
        $paths = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'api/')) {
                continue;
            }

            $path = '/'.preg_replace('/\{(\w+)\?\}/', '{$1}', $uri);
            $methods = array_values(array_intersect(
                $route->methods(),
                ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']
            ));

            if ($methods === []) {
                continue;
            }

            $action = $route->getActionName();
            $summary = str_contains($action, '@')
                ? class_basename(strtok($action, '@')).'#'.substr(strrchr($action, '@'), 1)
                : $action;

            foreach ($methods as $method) {
                $operation = [
                    'summary' => $summary,
                    'responses' => [
                        '200' => ['description' => 'OK'],
                        '401' => ['description' => 'Unauthenticated'],
                        '403' => ['description' => 'Forbidden'],
                        '422' => ['description' => 'Validation error'],
                    ],
                ];

                if ($method === 'GET' && isset(self::PAGED_SORTS[$path])) {
                    $operation['parameters'] = [
                        ['name' => 'q', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'maxLength' => 100], 'description' => 'Kata kunci pencarian.'],
                        ['name' => 'sort', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'enum' => self::allowedSortValues(self::PAGED_SORTS[$path])], 'description' => 'Kolom sort (prefix `-` untuk desc).'],
                        ['name' => 'per_page', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 15], 'description' => 'Jumlah item per halaman.'],
                    ];
                }

                $paths[$path][strtolower($method)] = $operation;
            }
        }

        ksort($paths);

        return response()->json([
            'openapi' => '3.1.0',
            'info' => [
                'title' => config('app.name').' API',
                'version' => 'v1+v2',
                'description' => 'Kontrak baca: v2 memakai envelope {ok, ...data}; v1 mobile legacy tanpa envelope.',
            ],
            'servers' => [['url' => rtrim(config('app.url'), '/').'/api']],
            'paths' => $paths,
            'components' => [
                'schemas' => [
                    'Meta' => [
                        'type' => 'object',
                        'description' => 'Envelope paging App\Support\ApiPaging: {data, meta}.',
                        'properties' => [
                            'current_page' => ['type' => 'integer'],
                            'last_page' => ['type' => 'integer'],
                            'per_page' => ['type' => 'integer'],
                            'total' => ['type' => 'integer'],
                        ],
                        'required' => ['current_page', 'last_page', 'per_page', 'total'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param  list<string>  $sorts
     * @return list<string>
     */
    private static function allowedSortValues(array $sorts): array
    {
        $values = [];

        foreach ($sorts as $kolom) {
            $values[] = $kolom;
            $values[] = '-'.$kolom;
        }

        return $values;
    }
}
