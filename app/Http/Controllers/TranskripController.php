<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMahasiswaData;
use App\Services\AkademikService;
use Illuminate\Contracts\View\View;

class TranskripController extends Controller
{
    use AuthorizesMahasiswaData;

    public function cetak(int $mahasiswaId): View
    {
        $this->authorizeMahasiswa($mahasiswaId);

        $ringkasan = AkademikService::ringkasan($mahasiswaId);

        return view('transkrip.cetak', [
            'mahasiswaId' => $mahasiswaId,
            'nilai' => $ringkasan['nilai'],
            'ipk' => $ringkasan['ipk'],
            'totalSks' => $ringkasan['total_sks'],
        ]);
    }
}
