<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMahasiswaData;
use App\Models\Mahasiswa;
use App\Models\Nilai;
use App\Models\TahunAkademik;
use App\Services\AkademikService;
use App\Services\AuditBacaService;
use App\Support\DokumenTte;
use App\Support\QrSvg;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TranskripController extends Controller
{
    use AuthorizesMahasiswaData;

    public function cetak(int $mahasiswaId, Request $request): View
    {
        $this->authorizeMahasiswa($mahasiswaId);
        AuditBacaService::catat(auth()->id(), 'transkrip', $mahasiswaId, $request->ip());

        $mahasiswa = Mahasiswa::with(['user', 'prodi'])->findOrFail($mahasiswaId);
        $ringkasan = AkademikService::ringkasan($mahasiswaId);
        $masaStudi = AkademikService::masaStudi((int) $mahasiswa->angkatan);
        $tahunFilter = $request->integer('tahun') ?: null;

        $nilaiQuery = Nilai::with('kelas.matakuliah', 'kelas.tahunAkademik')
            ->where('mahasiswa_id', $mahasiswaId)
            ->where('is_locked', true)
            ->when($tahunFilter, fn ($q) => $q->whereHas('kelas', fn ($k) => $k->where('tahun_akademik_id', $tahunFilter)))
            ->orderBy('id');

        $verifyUrl = route('transkrip.verify', [
            'mahasiswa' => $mahasiswaId,
            's' => DokumenTte::tanda($this->payload($mahasiswaId)),
        ]);

        return view('transkrip.cetak', [
            'mahasiswa' => $mahasiswa,
            'mahasiswaId' => $mahasiswaId,
            'nilai' => $nilaiQuery->paginate(25)->withQueryString(),
            'ipk' => $ringkasan['ipk'],
            'totalSks' => $ringkasan['total_sks'],
            'predikat' => self::predikat((float) $ringkasan['ipk']),
            'masaStudi' => $masaStudi,
            'tahunFilter' => $tahunFilter,
            'tahunList' => array_keys($ringkasan['per_tahun']),
            'tahunOptions' => TahunAkademik::whereIn('id', array_keys($ringkasan['per_tahun']))
                ->get(['id', 'tahun', 'semester'])
                ->mapWithKeys(fn ($t) => [$t->id => $t->tahun.' · '.ucfirst((string) $t->semester)])
                ->all(),
            'verifyUrl' => $verifyUrl,
            'qrSvg' => QrSvg::trySvg($verifyUrl),
            'tte' => DokumenTte::stamp('TRANSKRIP-'.$mahasiswa->nim.'-'.$mahasiswaId),
        ]);
    }

    /**
     * Verifikasi publik transkrip via QR (ditandatangani agar tak bisa dipalsukan).
     */
    public function verify(int $mahasiswa, Request $request): View|JsonResponse
    {
        $mhs = Mahasiswa::with(['user', 'prodi'])->findOrFail($mahasiswa);

        abort_unless(
            DokumenTte::valid($this->payload($mhs->id), (string) $request->query('s')),
            403,
            'Tanda verifikasi tidak valid.'
        );

        $ringkasan = AkademikService::ringkasan($mhs->id);

        if ($request->wantsJson()) {
            return response()->json([
                'dokumen' => 'transkrip',
                'nim' => $mhs->nim,
                'nama' => $mhs->user->name ?? null,
                'ipk' => $ringkasan['ipk'],
                'total_sks' => $ringkasan['total_sks'],
                'predikat' => self::predikat((float) $ringkasan['ipk']),
                'valid' => true,
            ]);
        }

        return view('transkrip.verifikasi', [
            'mahasiswa' => $mhs,
            'ipk' => $ringkasan['ipk'],
            'totalSks' => $ringkasan['total_sks'],
            'predikat' => self::predikat((float) $ringkasan['ipk']),
        ]);
    }

    public static function predikat(float $ipk): string
    {
        return match (true) {
            $ipk >= 3.51 => 'Dengan Pujian',
            $ipk >= 3.01 => 'Sangat Memuaskan',
            $ipk >= 2.76 => 'Memuaskan',
            default => 'Cukup',
        };
    }

    private function payload(int $mahasiswaId): string
    {
        return 'transkrip:'.$mahasiswaId;
    }
}
