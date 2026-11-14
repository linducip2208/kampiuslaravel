<?php

namespace App\Services\Ai;

use App\Models\Nilai;
use App\Models\TahunAkademik;
use App\Services\AkademikService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Rekomendasi MK: ulangi MK gagal (D/E) + batasi SKS dari IPK + ketersediaan kelas. */
class RekomendasiMkService
{
    /**
     * @return array{batas_sks: int, ipk: float, ulangi: list<array{kode: string, nama: string, huruf: string, matakuliah_id: int|null}>, kelas_tersedia: array<int, array{kelas_id: int, kelas: string, terisi: int, kuota: int, penuh: bool, posisi_waitlist: int|null}>, saran: string}
     */
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
                        'matakuliah_id' => $r->kelas->matakuliah->id ?? null,
                    ];
                }
            }
        } catch (\Throwable) {
        }

        $batas = AkademikService::batasSks($ipk);

        // M-026: petakan tiap MK gagal ke kelas periode aktif (sisa kuota +
        // posisi waitlist bila penuh). Tanpa periode aktif → peta kosong.
        $kelasTersedia = [];
        try {
            $aktifId = TahunAkademik::aktif()->value('id');

            if ($aktifId) {
                $mkIds = array_values(array_unique(array_filter(
                    array_map(fn ($u) => $u['matakuliah_id'], $ulangi)
                )));

                if ($mkIds !== []) {
                    $kelas = DB::table('kelas')
                        ->where('tahun_akademik_id', $aktifId)
                        ->whereIn('matakuliah_id', $mkIds)
                        ->select(['id', 'matakuliah_id', 'nama', 'kuota'])
                        ->orderBy('id')
                        ->get();

                    $terisiMap = AkademikService::kursiTerisiBanyak(
                        $kelas->pluck('id')->map(fn ($v): int => (int) $v)->all()
                    );

                    foreach ($kelas as $k) {
                        $kuota = (int) ($k->kuota ?? 0);
                        $terisi = $terisiMap[(int) $k->id] ?? 0;
                        $penuh = $kuota > 0 && $terisi >= $kuota;
                        $kelasTersedia[(int) $k->matakuliah_id] = [
                            'kelas_id' => (int) $k->id,
                            'kelas' => (string) ($k->nama ?? '#'.$k->id),
                            'terisi' => $terisi,
                            'kuota' => $kuota,
                            'penuh' => $penuh,
                            'posisi_waitlist' => $penuh
                                ? AkademikService::posisiWaitlist((int) $k->id, $mahasiswaId)
                                : null,
                        ];
                    }
                }
            }
        } catch (\Throwable) {
        }

        $saran = count($ulangi) > 0
            ? 'Prioritaskan mengulang '.count($ulangi).' MK (D/E) semester ini dalam batas '.$batas.' SKS.'
            : "Ambil MK lanjutan sesuai kurikulum dalam batas {$batas} SKS. Konsultasikan ke PA.";

        return ['batas_sks' => $batas, 'ipk' => $ipk, 'ulangi' => $ulangi, 'kelas_tersedia' => $kelasTersedia, 'saran' => $saran];
    }
}
