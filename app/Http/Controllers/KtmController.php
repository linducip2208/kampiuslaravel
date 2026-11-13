<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMahasiswaData;
use App\Models\Mahasiswa;
use App\Support\DokumenTte;
use App\Support\QrSvg;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kartu Tanda Mahasiswa (KTM) digital: kartu cetak + QR berisi NIM yang
 * menaut ke verifikasi publik.
 */
class KtmController extends Controller
{
    use AuthorizesMahasiswaData;

    public function cetak(int $mahasiswa): View
    {
        $this->authorizeMahasiswa($mahasiswa);

        $mhs = Mahasiswa::with(['user', 'prodi'])->findOrFail($mahasiswa);
        $verifyUrl = route('ktm.verify', ['nim' => $mhs->nim]);

        return view('cetak.ktm', [
            'mahasiswa' => $mhs,
            'verifyUrl' => $verifyUrl,
            'qrSvg' => QrSvg::trySvg($verifyUrl),
            'tte' => DokumenTte::stamp('KTM-'.$mhs->nim),
        ]);
    }

    /**
     * Verifikasi publik KTM berdasarkan NIM (setara pindai QR kartu).
     */
    public function verify(string $nim, Request $request): View|JsonResponse
    {
        $mhs = Mahasiswa::with(['user', 'prodi'])->where('nim', $nim)->firstOrFail();

        if ($request->wantsJson()) {
            return response()->json([
                'dokumen' => 'ktm',
                'nim' => $mhs->nim,
                'nama' => $mhs->user->name ?? null,
                'prodi' => $mhs->prodi->nama ?? null,
                'angkatan' => $mhs->angkatan,
                'status' => $mhs->status,
                'valid' => $mhs->status === 'aktif',
            ]);
        }

        return view('cetak.ktm-verify', ['mahasiswa' => $mhs]);
    }
}
