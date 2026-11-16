<?php

namespace App\Http\Controllers;

use App\Models\PayrollSlip;
use Illuminate\View\View;

/**
 * Ide bank: cetak slip gaji (print-CSS, tanpa PDF baru).
 * Otorisasi: peran keuangan/kasir/akuntansi/hrd/admin (lihat rute).
 */
class SlipController extends Controller
{
    public function cetak(int $slip): View
    {
        $slip = PayrollSlip::with(['pegawai', 'periode'])->findOrFail($slip);

        return view('cetak.slip', ['slip' => $slip]);
    }
}
