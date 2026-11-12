<?php

namespace App\Http\Controllers;

use App\Models\Nilai;
use Illuminate\Contracts\View\View;

class KhsController extends Controller
{
    public function show(int $mahasiswaId): View
    {
        $nilai = Nilai::where('mahasiswa_id', $mahasiswaId)->get();

        $ipk = $nilai->isEmpty() ? 0.0 : round($nilai->avg('bobot'), 2);
        $totalSks = 0;

        return view('khs.show', [
            'mahasiswaId' => $mahasiswaId,
            'nilai' => $nilai,
            'ipk' => $ipk,
            'ip' => $ipk,
            'totalSks' => $totalSks,
        ]);
    }
}
