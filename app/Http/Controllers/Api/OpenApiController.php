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
                $paths[$path][strtolower($method)] = [
                    'summary' => $summary,
                    'responses' => [
                        '200' => ['description' => 'OK'],
                        '401' => ['description' => 'Unauthenticated'],
                        '403' => ['description' => 'Forbidden'],
                        '422' => ['description' => 'Validation error'],
                    ],
                ];
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
        ]);
    }
}
