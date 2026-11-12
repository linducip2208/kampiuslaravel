<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use Illuminate\View\View;

class KwitansiController extends Controller
{
    public function cetak(int $pembayaran): View
    {
        $pembayaran = Pembayaran::with(['tagihan.mahasiswa', 'tagihan.tahunAkademik'])->findOrFail($pembayaran);

        abort_if($pembayaran->status !== 'verified', 403, 'Kwitansi hanya untuk pembayaran terverifikasi.');

        return view('cetak.kwitansi', ['pembayaran' => $pembayaran]);
    }
}
