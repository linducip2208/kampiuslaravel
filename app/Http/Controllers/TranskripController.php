<?php

namespace App\Http\Controllers;

use App\Models\Nilai;
use Illuminate\Contracts\View\View;

class TranskripController extends Controller
{
    public function cetak(int $mahasiswaId): View
    {
        $nilai = Nilai::where('mahasiswa_id', $mahasiswaId)->orderBy('kelas_id')->get();
        $ipk = $nilai->isEmpty() ? 0.0 : round($nilai->avg('bobot'), 2);

        return view('transkrip.cetak', [
            'mahasiswaId' => $mahasiswaId,
            'nilai' => $nilai,
            'ipk' => $ipk,
        ]);
    }
}
