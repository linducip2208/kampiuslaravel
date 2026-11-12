<?php

namespace App\Http\Controllers;

use App\Models\PmbGelombang;
use App\Models\PmbPendaftar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PmbController extends Controller
{
    public function create(): View
    {
        return view('pmb.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:100', 'unique:pmb_pendaftars,email'],
            'hp' => ['required', 'regex:/^[0-9+\- ]{9,16}$/'],
            'sekolah_asal' => ['required', 'string', 'min:3', 'max:150'],
            'gelombang_id' => ['required', 'integer', 'exists:pmb_gelombangs,id'],
            'prodi1_id' => ['required', 'integer', 'min:1'],
            'prodi2_id' => ['nullable', 'integer', 'min:1', 'different:prodi1_id'],
        ]);

        $gelombang = PmbGelombang::findOrFail($validated['gelombang_id']);

        if (! $gelombang->isBuka()) {
            return back()->withErrors(['gelombang_id' => 'Gelombang yang dipilih sudah tutup.'])->withInput();
        }

        $pendaftar = PmbPendaftar::query()->create([
            ...$validated,
            'no_daftar' => $this->generateNoDaftar(),
            'status' => PmbPendaftar::STATUS_DIAJUKAN,
        ]);

        return redirect()
            ->route('pmb.cek', ['no_daftar' => $pendaftar->no_daftar])
            ->with('sukses', "Pendaftaran berhasil! Nomor pendaftaran Anda: {$pendaftar->no_daftar}.");
    }

    public function cek(Request $request): View
    {
        $request->validate([
            'no_daftar' => ['nullable', 'string', 'max:30'],
        ]);

        $pendaftar = null;

        if ($request->filled('no_daftar')) {
            $pendaftar = PmbPendaftar::query()
                ->with('gelombang')
                ->where('no_daftar', $request->string('no_daftar')->toString())
                ->first();
        }

        return view('pmb.cek', ['pendaftar' => $pendaftar]);
    }

    private function generateNoDaftar(): string
    {
        do {
            $noDaftar = 'PMB-'.date('Y').'-'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (PmbPendaftar::query()->where('no_daftar', $noDaftar)->exists());

        return $noDaftar;
    }
}
