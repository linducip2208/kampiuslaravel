<?php

namespace App\Http\Controllers;

use App\Models\Matakuliah;
use App\Models\Pengumuman;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Pencarian global katalog kampus (pengumuman, prodi, matakuliah).
 * Hanya data katalog publik — tanpa data pribadi mahasiswa/keuangan.
 */
class PencarianController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $hasil = ['pengumuman' => collect(), 'prodi' => collect(), 'matakuliah' => collect()];

        if (mb_strlen($q) >= 2) {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';

            if (Schema::hasTable('pengumuman')) {
                $hasil['pengumuman'] = Pengumuman::whereNotNull('published_at')
                    ->where(function ($w) use ($like): void {
                        $w->where('judul', 'like', $like)->orWhere('isi', 'like', $like);
                    })->latest('published_at')->take(8)->get();
            }
            if (Schema::hasTable('prodi')) {
                $hasil['prodi'] = Prodi::where('nama', 'like', $like)
                    ->orWhere('kode', 'like', $like)->orderBy('nama')->take(8)->get();
            }
            if (Schema::hasTable('matakuliah')) {
                $hasil['matakuliah'] = Matakuliah::with('prodi')
                    ->where('nama', 'like', $like)->orWhere('kode', 'like', $like)
                    ->orderBy('kode')->take(8)->get();
            }
        }

        return view('pencarian.index', ['q' => $q, 'hasil' => $hasil]);
    }
}
