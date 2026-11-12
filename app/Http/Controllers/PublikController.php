<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use App\Models\Prodi;
use Illuminate\Support\Str;
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

    public function dokumen(?string $file = null): View
    {
        $dokumen = [
            'ARSITEKTUR' => 'Arsitektur sistem',
            'ERP_MODULES' => 'Modul ERP',
            'ROLES_PERMISSIONS' => 'Role & permission',
            'INSTALLATION' => 'Instalasi',
            'USER_GUIDE' => 'Panduan pengguna',
            'INTEGRASI' => 'Integrasi (payment, push, PDDikti)',
            'COMFYUI' => 'Generate gambar AI',
            'SECURITY' => 'Keamanan',
            'TESTING' => 'Testing',
            'CHANGELOG' => 'Changelog',
        ];

        $konten = null;
        $aktif = null;

        if ($file !== null) {
            abort_unless(array_key_exists($file, $dokumen), 404);
            $path = base_path('docs/'.$file.'.md');
            abort_unless(is_file($path), 404);
            $konten = Str::markdown((string) file_get_contents($path));
            $aktif = $file;
        }

        return view('publik.dokumen', ['dokumen' => $dokumen, 'konten' => $konten, 'aktif' => $aktif]);
    }
}
