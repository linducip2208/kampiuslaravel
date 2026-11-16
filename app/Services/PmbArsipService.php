<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\PmbGelombang;
use App\Models\PmbPendaftar;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Arsip berkas pendaftar per gelombang (ZIP + manifest + AuditLog).
 * Retensi/penghapusan fisik tetap manual pasca-unduh (keputusan admin).
 */
class PmbArsipService
{
    public static function arsipGelombang(int $gelombangId, ?int $userId = null): string
    {
        $gelombang = PmbGelombang::findOrFail($gelombangId);

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP zip tidak tersedia.');
        }

        $dir = 'exports/pmb-arsip';
        Storage::disk('local')->makeDirectory($dir);
        $nama = sprintf('arsip-pmb-g%d-%s.zip', $gelombang->id, now()->format('Ymd-His'));
        $path = Storage::disk('local')->path("{$dir}/{$nama}");

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat arsip ZIP.');
        }

        $entri = [];

        try {
            $pendaftars = PmbPendaftar::where('gelombang_id', $gelombang->id)->orderBy('no_daftar')->get();

            foreach ($pendaftars as $p) {
                foreach ((array) ($p->berkas_json ?? []) as $jenis => $file) {
                    $namaBerkas = "pmb/{$p->no_daftar}/".basename((string) $file);

                    if ($file && Storage::disk('public')->exists((string) $file)) {
                        $zip->addFromString($namaBerkas, (string) Storage::disk('public')->get((string) $file));
                        $entri[] = ['no_daftar' => $p->no_daftar, 'jenis' => $jenis, 'berkas' => $namaBerkas, 'status' => 'ada'];
                    } else {
                        $entri[] = ['no_daftar' => $p->no_daftar, 'jenis' => $jenis, 'berkas' => $namaBerkas, 'status' => 'hilang'];
                    }
                }
            }

            $zip->addFromString('manifest.json', (string) json_encode([
                'gelombang_id' => $gelombang->id,
                'gelombang' => $gelombang->nama ?? null,
                'diarsip_at' => now()->toIso8601String(),
                'jumlah_pendaftar' => $pendaftars->count(),
                'entri' => $entri,
            ], JSON_PRETTY_PRINT));
            $zip->close();
        } catch (\Throwable $e) {
            $zip->close();
            throw $e;
        }

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'pmb.arsip',
            'model' => PmbGelombang::class,
            'model_id' => $gelombang->id,
            'diff_json' => ['berkas' => count($entri), 'arsip' => $nama],
            'created_at' => now(),
        ]);

        return $path;
    }
}
