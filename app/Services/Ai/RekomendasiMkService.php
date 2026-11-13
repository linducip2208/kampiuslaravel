<?php

namespace App\Services\Ai;

use App\Models\Nilai;
use App\Services\AkademikService;
use Illuminate\Support\Facades\Schema;

/** Rekomendasi MK: ulangi MK gagal (D/E) + batasi SKS dari IPK. */
class RekomendasiMkService
{
    /** @return array{batas_sks: int, ipk: float, ulangi: list<array{kode: string, nama: string, huruf: string}>, saran: string} */
    public function rekomendasi(int $mahasiswaId): array
    {
        $ipk = 0.0;
        $ulangi = [];

        try {
            if (Schema::hasTable('nilai')) {
                $ipk = (float) (AkademikService::ringkasan($mahasiswaId)['ipk'] ?? 0.0);
                $rows = Nilai::with('kelas.matakuliah')->where('mahasiswa_id', $mahasiswaId)->whereIn('huruf', ['D', 'E'])->get();
                foreach ($rows as $r) {
                    $ulangi[] = [
                        'kode' => (string) ($r->kelas->matakuliah->kode ?? '-'),
                        'nama' => (string) ($r->kelas->matakuliah->nama ?? 'MK'),
                        'huruf' => (string) $r->huruf,
                    ];
                }
            }
        } catch (\Throwable) {
        }

        $batas = AkademikService::batasSks($ipk);
        $saran = count($ulangi) > 0
            ? 'Prioritaskan mengulang '.count($ulangi).' MK (D/E) semester ini dalam batas '.$batas.' SKS.'
            : "Ambil MK lanjutan sesuai kurikulum dalam batas {$batas} SKS. Konsultasikan ke PA.";

        return ['batas_sks' => $batas, 'ipk' => $ipk, 'ulangi' => $ulangi, 'saran' => $saran];
    }
}
