<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMahasiswaData;
use App\Models\Pembayaran;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KwitansiController extends Controller
{
    use AuthorizesMahasiswaData;

    public function cetak(int $pembayaran): View
    {
        $pembayaran = Pembayaran::with(['tagihan.mahasiswa', 'tagihan.tahunAkademik'])->findOrFail($pembayaran);

        abort_if($pembayaran->status !== 'verified', 403, 'Kwitansi hanya untuk pembayaran terverifikasi.');
        $this->authorizeMahasiswa((int) $pembayaran->tagihan->mahasiswa_id);

        // Rincian pajak: nominal dianggap sudah termasuk PPN bila tarif > 0.
        $tarif = (float) (Setting::get('pajak.ppn_persen', '0') ?? '0');
        $bruto = (int) $pembayaran->nominal;
        $dpp = $tarif > 0 ? (int) round($bruto * 100 / (100 + $tarif)) : $bruto;

        return view('cetak.kwitansi', [
            'pembayaran' => $pembayaran,
            'pajak' => [
                'npwp' => (string) (Setting::get('pajak.npwp', '') ?? ''),
                'tarif' => $tarif,
                'dpp' => $dpp,
                'ppn' => $bruto - $dpp,
                'bruto' => $bruto,
            ],
        ]);
    }

    /**
     * Kuitansi gabungan: satu dokumen untuk semua pembayaran terverifikasi
     * milik mahasiswa pada tahun akademik tertentu.
     */
    public function gabungan(Request $request): View
    {
        $mahasiswaId = $request->integer('mahasiswa_id');
        $tahunId = $request->integer('tahun_akademik_id');

        abort_unless($mahasiswaId > 0 && $tahunId > 0, 422, 'mahasiswa_id dan tahun_akademik_id wajib diisi.');
        $this->authorizeMahasiswa($mahasiswaId);

        $bayar = Pembayaran::with(['tagihan.tahunAkademik'])
            ->where('status', 'verified')
            ->whereHas('tagihan', fn ($q) => $q->where('mahasiswa_id', $mahasiswaId)->where('tahun_akademik_id', $tahunId))
            ->orderBy('verified_at')
            ->orderBy('id')
            ->get();

        abort_if($bayar->isEmpty(), 404, 'Tidak ada pembayaran terverifikasi pada periode tersebut.');

        return view('cetak.kwitansi-gabungan', [
            'bayar' => $bayar,
            'mahasiswa' => $bayar->first()->tagihan->mahasiswa,
            'total' => $bayar->sum('nominal'),
        ]);
    }
}
