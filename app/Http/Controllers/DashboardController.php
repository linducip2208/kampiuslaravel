<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\Prodi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'stats' => [
                'mahasiswa' => Mahasiswa::count(),
                'dosen' => Dosen::count(),
                'prodi' => Prodi::count(),
                'matakuliah' => Matakuliah::count(),
            ],
        ]);
    }

    public function pimpinan(): View
    {
        $stats = [];
        foreach (['mahasiswa' => 'mahasiswa', 'dosen' => 'dosen', 'skripsi_judul' => 'skripsi_judul', 'sidang' => 'sidang', 'tagihan' => 'tagihan', 'pembayaran' => 'pembayaran', 'surat' => 'surat', 'pengumuman' => 'pengumuman'] as $key => $table) {
            $stats[$key] = Schema::hasTable($table) ? DB::table($table)->count() : 0;
        }

        $stats['tagihan_belum_bayar'] = Schema::hasTable('tagihan')
            ? DB::table('tagihan')->whereIn('status', ['belum_bayar', 'sebagian'])->count()
            : 0;

        $stats['pembayaran_pending'] = Schema::hasTable('pembayaran')
            ? DB::table('pembayaran')->where('status', 'pending')->count()
            : 0;

        return view('dashboard.pimpinan', ['stats' => $stats]);
    }
}
