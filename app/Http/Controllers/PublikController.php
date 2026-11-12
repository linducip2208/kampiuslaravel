<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use App\Models\Prodi;
use Illuminate\View\View;

class PublikController extends Controller
{
    public function prodi(): View
    {
        return view('publik.prodi-index', [
            'prodi' => Prodi::with('fakultas')->orderBy('nama')->get(),
        ]);
    }

    public function prodiDetail(string $slug): View
    {
        $prodi = Prodi::with(['fakultas', 'matakuliah'])->where('slug', $slug)->firstOrFail();

        return view('publik.prodi-show', [
            'prodi' => $prodi,
            'berita' => Pengumuman::where('prodi_id', $prodi->id)
                ->whereNotNull('published_at')
                ->latest('published_at')
                ->take(5)
                ->get(),
        ]);
    }

    public function pengumuman(): View
    {
        return view('publik.pengumuman-index', [
            'pengumuman' => Pengumuman::with('prodi')
                ->whereNotNull('published_at')
                ->latest('published_at')
                ->paginate(12),
        ]);
    }

    public function pengumumanDetail(int $id): View
    {
        $item = Pengumuman::with('prodi')->whereNotNull('published_at')->findOrFail($id);

        return view('publik.pengumuman-show', ['item' => $item]);
    }
}
