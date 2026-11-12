<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMahasiswaData;
use App\Models\Krs;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KrsController extends Controller
{
    use AuthorizesMahasiswaData;

    /**
     * Cetak KRS (tampilan print-friendly, tanpa package baru).
     */
    public function cetak(Request $request, Krs $krs): View
    {
        $this->authorizeMahasiswa((int) $krs->mahasiswa_id);

        $krs->load('details.kelas.matakuliah');

        return view('krs.cetak', ['krs' => $krs]);
    }
}
