<?php

namespace App\Http\Controllers;

use App\Models\PmbGelombang;
use App\Models\PmbPendaftar;
use App\Services\PmbVaService;
use App\Services\WaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PmbController extends Controller
{
    public function create(): View
    {
        return view('pmb.create');
    }

    public function store(Request $request, PmbVaService $va, WaService $wa): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:100', 'unique:pmb_pendaftars,email'],
            'hp' => ['required', 'regex:/^[0-9+\- ]{9,16}$/'],
            'sekolah_asal' => ['required', 'string', 'min:3', 'max:150'],
            'referral_kode' => ['nullable', 'string', 'max:30', 'exists:pmb_referrals,kode'],
            'beasiswa_id' => ['nullable', 'integer', 'exists:beasiswa,id'],
            'gelombang_id' => ['required', 'integer', 'exists:pmb_gelombangs,id'],
            'prodi1_id' => ['required', 'integer', 'exists:prodi,id'],
            'prodi2_id' => ['nullable', 'integer', 'exists:prodi,id', 'different:prodi1_id'],
        ]);

        $gelombang = PmbGelombang::findOrFail($validated['gelombang_id']);

        if (! $gelombang->isBuka()) {
            return back()->withErrors(['gelombang_id' => 'Gelombang yang dipilih sudah tutup.'])->withInput();
        }

        if ($gelombang->kuotaPenuh()) {
            return back()->withErrors(['gelombang_id' => 'Kuota gelombang ini sudah penuh.'])->withInput();
        }

        $pendaftar = DB::transaction(function () use ($validated, $va) {
            $g = PmbGelombang::whereKey($validated['gelombang_id'])->lockForUpdate()->firstOrFail();
            if (! $g->isBuka() || $g->kuotaPenuh()) {
                abort(422, 'Kuota gelombang ini sudah penuh.');
            }

            $p = PmbPendaftar::query()->create([
                ...$validated,
                'no_daftar' => $this->generateNoDaftar(),
                'status' => PmbPendaftar::STATUS_DIAJUKAN,
            ]);
            $va->issueFor($p);

            return $p;
        });

        $infoVa = ['va_number' => $pendaftar->fresh()?->va_number ?? '-', 'nominal' => app(PmbVaService::class)->nominal($pendaftar->fresh() ?? $pendaftar)];
        try {
            $wa->welcome($pendaftar->fresh() ?? $pendaftar);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()
            ->route('pmb.cek', ['no_daftar' => $pendaftar->no_daftar])
            ->with('sukses', "Pendaftaran berhasil! Nomor pendaftaran Anda: {$pendaftar->no_daftar}. ".
                "VA biaya pendaftaran: {$infoVa['va_number']} (Rp ".number_format($infoVa['nominal'], 0, ',', '.').').');
    }

    public function cek(Request $request): View
    {
        $request->validate([
            'no_daftar' => ['nullable', 'string', 'max:30'],
        ]);

        $pendaftar = null;

        if ($request->filled('no_daftar')) {
            $pendaftar = PmbPendaftar::query()
                ->with(['gelombang', 'nilaiSeleksi', 'prodi1', 'prodi2'])
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
