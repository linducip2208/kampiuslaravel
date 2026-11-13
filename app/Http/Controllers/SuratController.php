<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMahasiswaData;
use App\Models\Surat;
use App\Support\DokumenTte;
use App\Support\QrSvg;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SuratController extends Controller
{
    use AuthorizesMahasiswaData;

    public function cetak(int $surat): View
    {
        $surat = Surat::with(['mahasiswa.user', 'mahasiswa.prodi'])->findOrFail($surat);

        abort_if(! $surat->nomor_surat, 403, 'Surat belum memiliki nomor.');
        $this->authorizeMahasiswa((int) $surat->mahasiswa_id);

        $verifyUrl = route('surat.verify', ['nomor' => $surat->nomor_surat]);

        return view('cetak.surat', [
            'surat' => $surat,
            'template' => self::template($surat->jenis),
            'verifyUrl' => $verifyUrl,
            'qrSvg' => QrSvg::trySvg($verifyUrl),
            'tte' => DokumenTte::stamp($surat->nomor_surat),
        ]);
    }

    public function verify(string $nomor): View|JsonResponse
    {
        $surat = Surat::with(['mahasiswa.user', 'mahasiswa.prodi'])->where('nomor_surat', $nomor)->firstOrFail();

        if (request()->wantsJson()) {
            return response()->json([
                'nomor_surat' => $surat->nomor_surat,
                'jenis' => $surat->jenis,
                'status' => $surat->status,
                'nim' => $surat->mahasiswa->nim ?? null,
                'nama' => $surat->mahasiswa->user->name ?? null,
                'valid' => $surat->status === 'selesai',
            ]);
        }

        return view('cetak.surat-verify', ['surat' => $surat]);
    }

    /**
     * @return array{judul:string, perihal:string, paragraf:list<string>}
     */
    public static function template(string $jenis): array
    {
        return match ($jenis) {
            'aktif_kuliah' => [
                'judul' => 'Surat Keterangan Aktif Kuliah',
                'perihal' => 'Keterangan mahasiswa aktif',
                'paragraf' => [
                    'Yang bertanda tangan di bawah ini menerangkan bahwa mahasiswa berikut tercatat aktif dan sedang menjalani perkuliahan pada semester berjalan.',
                    'Surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.',
                ],
            ],
            'pengantar_kp' => [
                'judul' => 'Surat Pengantar Kerja Praktik',
                'perihal' => 'Permohonan kerja praktik / magang',
                'paragraf' => [
                    'Sehubungan dengan kewajiban kurikulum, mahasiswa berikut ditugaskan melaksanakan Kerja Praktik (KP) / magang pada instansi/perusahaan tujuan.',
                    'Kami memohon kesediaan instansi tujuan menerima mahasiswa tersebut. Atas perhatian dan kerja sama yang diberikan, kami mengucapkan terima kasih.',
                ],
            ],
            'keterangan_lulus' => [
                'judul' => 'Surat Keterangan Lulus',
                'perihal' => 'Keterangan kelulusan sementara (ijazah dalam proses)',
                'paragraf' => [
                    'Yang bertanda tangan di bawah ini menerangkan bahwa mahasiswa berikut telah menyelesaikan seluruh kewajiban akademik dan dinyatakan LULUS.',
                    'Ijazah dan transkrip resmi sedang dalam proses penerbitan. Surat keterangan ini bersifat sementara dan dibuat untuk dipergunakan sebagaimana mestinya.',
                ],
            ],
            default => [
                'judul' => 'Surat Permohonan Legalisir',
                'perihal' => 'Permohonan legalisir dokumen akademik',
                'paragraf' => [
                    'Mahasiswa/alumni berikut mengajukan permohonan legalisir dokumen akademik (ijazah/transkrip) sesuai ketentuan yang berlaku.',
                    'Berkas pendukung telah diverifikasi oleh bagian akademik. Surat ini dibuat untuk dipergunakan sebagaimana mestinya.',
                ],
            ],
        };
    }
}
