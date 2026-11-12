<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMahasiswaData;
use App\Models\Pembayaran;
use Illuminate\View\View;

class KwitansiController extends Controller
{
    use AuthorizesMahasiswaData;

    public function cetak(int $pembayaran): View
    {
        $pembayaran = Pembayaran::with(['tagihan.mahasiswa', 'tagihan.tahunAkademik'])->findOrFail($pembayaran);

        abort_if($pembayaran->status !== 'verified', 403, 'Kwitansi hanya untuk pembayaran terverifikasi.');
        $this->authorizeMahasiswa((int) $pembayaran->tagihan->mahasiswa_id);

        return view('cetak.kwitansi', ['pembayaran' => $pembayaran]);
    }
}
