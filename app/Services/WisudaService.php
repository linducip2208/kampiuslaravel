<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\Sidang;
use App\Models\SkripsiJudul;

/**
 * Cek syarat kelulusan (baca-saja, untuk pratinjau sebelum yudisium).
 * Penolakan tetap di `Yudisium::luluskan` (tidak diubah) — service ini
 * hanya memberi daftar periksa yang sama + info SKS vs acuan jenjang.
 *
 * @return array{lolos: bool, items: list<array{kode: string, label: string, lolos: bool, blokir: bool, detail: string}>}
 */
class WisudaService
{
    public static function acuanSks(?string $jenjang): int
    {
        return match (strtoupper((string) $jenjang)) {
            'D3' => 110,
            'S2' => 36,
            default => 144,
        };
    }

    public static function cekSyarat(int $mahasiswaId): array
    {
        $mhs = Mahasiswa::with('prodi')->find($mahasiswaId);

        if (! $mhs) {
            return ['lolos' => false, 'items' => [[
                'kode' => 'mahasiswa', 'label' => 'Data mahasiswa', 'lolos' => false,
                'blokir' => true, 'detail' => 'tidak ditemukan',
            ]]];
        }

        $tunggakan = KeuanganService::hasTunggakan($mhs->id);
        $ipk = (float) (AkademikService::ringkasan($mhs->id)['ipk'] ?? 0.0);
        $totalSks = (int) (AkademikService::ringkasan($mhs->id)['total_sks'] ?? 0);
        $sidangLulus = Sidang::where('status', 'lulus')
            ->whereIn('judul_id', SkripsiJudul::where('mahasiswa_id', $mhs->id)->pluck('id'))
            ->exists();
        $acuan = self::acuanSks($mhs->prodi->jenjang ?? null);

        $items = [
            [
                'kode' => 'tunggakan', 'label' => 'Bebas tunggakan',
                'lolos' => ! $tunggakan, 'blokir' => true,
                'detail' => $tunggakan ? 'masih ada tunggakan' : 'bersih',
            ],
            [
                'kode' => 'ipk', 'label' => 'IPK ≥ 2.00',
                'lolos' => $ipk >= 2.0, 'blokir' => true,
                'detail' => "IPK {$ipk}",
            ],
            [
                'kode' => 'sidang', 'label' => 'Lulus sidang',
                'lolos' => $sidangLulus, 'blokir' => true,
                'detail' => $sidangLulus ? 'ada sidang lulus' : 'belum ada sidang lulus',
            ],
            [
                'kode' => 'sks', 'label' => "SKS terkunci (acuan {$acuan})",
                'lolos' => $totalSks >= $acuan, 'blokir' => false,
                'detail' => "{$totalSks} SKS (acuan, bukan penolakan otomatis)",
            ],
        ];

        $lolos = true;
        foreach ($items as $it) {
            if ($it['blokir'] && ! $it['lolos']) {
                $lolos = false;
                break;
            }
        }

        return ['lolos' => $lolos, 'items' => $items];
    }
}
