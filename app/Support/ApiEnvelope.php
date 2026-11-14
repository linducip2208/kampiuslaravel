<?php

namespace App\Support;

/**
 * M-045: envelope JSON konsisten untuk API.
 * Sukses: {ok:true, ...data}. Gagal: {ok:false, message}.
 * Backward-compat: kunci data lama dipertahankan di top-level
 * (bukan dibungkus `data`) agar kontrak v1/v2 lama tidak pecah.
 */
final class ApiEnvelope
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function ok(array $data = [], array $meta = []): array
    {
        return array_merge(['ok' => true], $data, $meta === [] ? [] : ['meta' => $meta]);
    }

    public static function fail(string $message, array $extra = []): array
    {
        return array_merge(['ok' => false, 'message' => $message], $extra);
    }
}
