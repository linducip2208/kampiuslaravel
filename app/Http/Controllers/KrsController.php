<?php

namespace App\Http\Controllers;

use App\Models\Krs;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KrsController extends Controller
{
    /**
     * Cetak KRS (tampilan print-friendly, tanpa package baru).
     */
    public function cetak(Request $request, Krs $krs): View
    {
        $krs->load('details');

        return view('krs.cetak', ['krs' => $krs]);
    }
}
