<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMahasiswaData;
use App\Models\Mahasiswa;
use App\Models\Nilai;
use App\Models\TahunAkademik;
use App\Services\AkademikService;
use App\Support\DokumenTte;
use App\Support\QrSvg;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KhsController extends Controller
{
    use AuthorizesMahasiswaData;

    public function show(int $mahasiswaId, Request $request): View
    {
        $this->authorizeMahasiswa($mahasiswaId);

        $mahasiswa = Mahasiswa::with(['user', 'prodi'])->findOrFail($mahasiswaId);
        // IPK resmi tetap dari ringkasan penuh (nilai terkunci); tabel
        // display dipaginasi + bisa difilter per periode (M-024, anti-unbounded).
        $ringkasan = AkademikService::ringkasan($mahasiswaId);
        $tahunFilter = $request->integer('tahun') ?: null;

        $nilaiQuery = Nilai::with('kelas.matakuliah', 'kelas.tahunAkademik')
            ->where('mahasiswa_id', $mahasiswaId)
            ->where('is_locked', true)
            ->when($tahunFilter, fn ($q) => $q->whereHas('kelas', fn ($k) => $k->where('tahun_akademik_id', $tahunFilter)))
            ->orderBy('id');

        $verifyUrl = route('khs.verify', [
            'mahasiswa' => $mahasiswaId,
            's' => DokumenTte::tanda($this->payload($mahasiswaId)),
        ]);

        return view('khs.show', [
            'mahasiswa' => $mahasiswa,
            'mahasiswaId' => $mahasiswaId,
            'nilai' => $nilaiQuery->paginate(25)->withQueryString(),
            'ipk' => $ringkasan['ipk'],
            'ip' => $this->ipSemesterBerjalan($ringkasan),
            'totalSks' => $ringkasan['total_sks'],
            'perTahun' => $ringkasan['per_tahun'],
            'tahunFilter' => $tahunFilter,
            'tahunList' => array_keys($ringkasan['per_tahun']),
            'tahunOptions' => TahunAkademik::whereIn('id', array_keys($ringkasan['per_tahun']))
                ->get(['id', 'tahun', 'semester'])
                ->mapWithKeys(fn ($t) => [$t->id => $t->tahun.' · '.ucfirst((string) $t->semester)])
                ->all(),
            'verifyUrl' => $verifyUrl,
            'qrSvg' => QrSvg::trySvg($verifyUrl),
            'tte' => DokumenTte::stamp('KHS-'.$mahasiswa->nim.'-'.$mahasiswaId),
        ]);
    }

    /**
     * IP semester berjalan = IP tahun akademik terbaru (bukan IPK global).
     *
     * @param  array{per_tahun: array<int, array{ip: float, sks: int}>}  $ringkasan
     */
    private function ipSemesterBerjalan(array $ringkasan): float
    {
        $perTahun = $ringkasan['per_tahun'] ?? [];

        if ($perTahun === []) {
            return 0.0;
        }

        return (float) ($perTahun[max(array_keys($perTahun))]['ip'] ?? 0.0);
    }

    /**
     * Verifikasi publik dokumen KHS via QR (ditandatangani agar tak bisa dipalsukan).
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
                'dokumen' => 'khs',
                'nim' => $mhs->nim,
                'nama' => $mhs->user->name ?? null,
                'ipk' => $ringkasan['ipk'],
                'total_sks' => $ringkasan['total_sks'],
                'jumlah_matakuliah' => $ringkasan['nilai']->count(),
                'valid' => true,
            ]);
        }

        return view('khs.verifikasi', [
            'mahasiswa' => $mhs,
            'ipk' => $ringkasan['ipk'],
            'totalSks' => $ringkasan['total_sks'],
            'jumlahMatakuliah' => $ringkasan['nilai']->count(),
        ]);
    }

    private function payload(int $mahasiswaId): string
    {
        return 'khs:'.$mahasiswaId;
    }
}
