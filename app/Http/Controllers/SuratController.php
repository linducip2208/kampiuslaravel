<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMahasiswaData;
use App\Models\Surat;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SuratController extends Controller
{
    use AuthorizesMahasiswaData;

    public function cetak(int $surat): View
    {
        $surat = Surat::with('mahasiswa')->findOrFail($surat);

        abort_if(! $surat->nomor_surat, 403, 'Surat belum memiliki nomor.');
        $this->authorizeMahasiswa((int) $surat->mahasiswa_id);

        return view('cetak.surat', [
            'surat' => $surat,
            'verifyUrl' => route('surat.verify', ['nomor' => $surat->nomor_surat]),
        ]);
    }

    public function verify(string $nomor): View|JsonResponse
    {
        $surat = Surat::with('mahasiswa')->where('nomor_surat', $nomor)->firstOrFail();

        if (request()->wantsJson()) {
            return response()->json([
                'nomor_surat' => $surat->nomor_surat,
                'jenis' => $surat->jenis,
                'status' => $surat->status,
                'valid' => $surat->status === 'selesai',
            ]);
        }

        return view('cetak.surat-verify', ['surat' => $surat]);
    }
}
