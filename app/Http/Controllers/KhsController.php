<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMahasiswaData;
use App\Services\AkademikService;
use Illuminate\Contracts\View\View;

class KhsController extends Controller
{
    use AuthorizesMahasiswaData;

    public function show(int $mahasiswaId): View
    {
        $this->authorizeMahasiswa($mahasiswaId);

        $ringkasan = AkademikService::ringkasan($mahasiswaId);

        return view('khs.show', [
            'mahasiswaId' => $mahasiswaId,
            'nilai' => $ringkasan['nilai'],
            'ipk' => $ringkasan['ipk'],
            'ip' => $ringkasan['ipk'],
            'totalSks' => $ringkasan['total_sks'],
            'perTahun' => $ringkasan['per_tahun'],
        ]);
    }
}
