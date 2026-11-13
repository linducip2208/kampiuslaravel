<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMahasiswaData;
use App\Models\Pembayaran;
use App\Models\Setting;
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
}
