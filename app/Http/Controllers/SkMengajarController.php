<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\SkMengajar;
use App\Models\TahunAkademik;
use App\Services\BkdService;
use App\Support\DokumenTte;
use App\Support\QrSvg;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ide bank: dokumen SK mengajar (template + QR verifikasi, pola Surat).
 * Tanpa PDF baru: cetak via print-CSS seperti transkrip.
 */
class SkMengajarController extends Controller
{
    private function authorizeSk(int $dosenId): void
    {
        $user = auth()->user();

        abort_unless($user, 403);

        if ($user->hasAnyRole(['superadmin', 'admin_prodi'])) {
            return;
        }

        abort_unless(
            (int) Dosen::where('user_id', $user->id)->value('id') === $dosenId,
            403,
            'Hanya SK milik sendiri.'
        );
    }

    public function cetak(int $dosen, int $tahun, Request $request): View
    {
        $this->authorizeSk($dosen);

        $tahunAkademik = TahunAkademik::findOrFail($tahun);
        $sk = BkdService::generateSk($dosen, $tahun);
        $sk->loadMissing(['dosen.user']);

        $verifyUrl = route('sk-mengajar.verify', [
            'nomor' => $sk->nomor,
            's' => DokumenTte::tanda($this->payload($sk->nomor)),
        ]);

        return view('cetak.sk-mengajar', [
            'sk' => $sk,
            'tahunAkademik' => $tahunAkademik,
            'verifyUrl' => $verifyUrl,
            'qrSvg' => QrSvg::trySvg($verifyUrl),
            'tte' => DokumenTte::stamp($sk->nomor),
        ]);
    }

    public function verify(string $nomor, Request $request): View|JsonResponse
    {
        abort_unless(DokumenTte::valid($this->payload($nomor), (string) $request->query('s')), 403, 'Tanda verifikasi tidak valid.');

        $sk = SkMengajar::with(['dosen.user', 'tahunAkademik'])->where('nomor', $nomor)->firstOrFail();

        if ($request->wantsJson()) {
            return response()->json([
                'dokumen' => 'sk-mengajar',
                'nomor' => $sk->nomor,
                'dosen' => $sk->dosen->user->name ?? null,
                'nidn' => $sk->dosen->nidn ?? null,
                'total_sks' => $sk->total_sks,
                'valid' => true,
            ]);
        }

        return view('cetak.sk-mengajar-verify', ['sk' => $sk]);
    }

    private function payload(string $nomor): string
    {
        return 'sk-mengajar:'.$nomor;
    }
}
