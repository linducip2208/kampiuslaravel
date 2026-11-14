<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\PddiktiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PddiktiController extends Controller
{
    /** Peta tabel lokal → act Feeder untuk live-sync per-record. */
    private const LIVE_ACTS = [
        'mahasiswa' => 'InsertMhs',
        'nilai' => 'InsertNilai',
        'kelulusan' => 'InsertKelulusan',
    ];

    public function index(): View
    {
        return view('pddikti.index');
    }

    /** Daftar act Feeder yang diizinkan (allowlist — M-005, act liar → 422). */
    public const ALLOWED_ACTS = [
        'GetToken',
        'InsertMhs', 'UpdateMhs', 'DeleteMhs', 'GetMhs',
        'InsertNilai', 'UpdateNilai', 'DeleteNilai', 'GetNilai',
        'InsertKelulusan', 'UpdateKelulusan', 'GetKelulusan',
    ];

    public function export(string $jenis, PddiktiService $feeder): StreamedResponse
    {
        abort_unless(in_array($jenis, ['mahasiswa', 'nilai', 'kelulusan'], true), 404);

        $path = $feeder->exportCsv($jenis);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'pddikti.export',
            'model' => 'pddikti',
            'model_id' => 0,
            'diff_json' => ['jenis' => $jenis, 'path' => $path],
            'created_at' => now(),
        ]);

        return Storage::download($path);
    }

    public function push(Request $request, PddiktiService $feeder): JsonResponse
    {
        $validated = $request->validate([
            'act' => ['required', 'string', 'max:60', 'in:'.implode(',', self::ALLOWED_ACTS)],
            'record' => 'required|array',
        ]);

        return response()->json($feeder->push($validated['act'], $validated['record']));
    }

    /** Status kesiapan Feeder live-sync (tanpa panggil jaringan — murah & cacheable). */
    public function status(): JsonResponse
    {
        $base = (string) Setting::get('pddikti.base_url', '');

        return response()->json([
            'live' => Setting::get('pddikti.live_enabled', '0') === '1',
            'base_url_set' => $base !== '',
            'acts' => self::LIVE_ACTS,
        ]);
    }

    /** Live-sync satu record: validasi tabel → map ke act → push via token Feeder. */
    public function sync(Request $request, PddiktiService $feeder): JsonResponse
    {
        $validated = $request->validate([
            'tabel' => 'required|string|in:mahasiswa,nilai,kelulusan',
            'record' => 'required|array',
        ]);

        return response()->json($feeder->push(self::LIVE_ACTS[$validated['tabel']], $validated['record']));
    }

    /** Sinkron profil Sinta (riset): forward nidn ke endpoint Sinta kampus. */
    public function sinta(Request $request): JsonResponse
    {
        $validated = $request->validate(['nidn' => 'required|string|max:30']);

        $base = (string) Setting::get('sinta.base_url', '');

        if ($base === '') {
            return response()->json(['message' => 'Integrasi Sinta belum dikonfigurasi di halaman Integrasi (sinta.base_url).'], 422);
        }

        try {
            $res = Http::withToken((string) Setting::get('sinta.api_key', ''))
                ->timeout(30)
                ->post(rtrim($base, '/').'/profil', ['nidn' => $validated['nidn']]);

            return response()->json($res->json() ?? ['error' => 1, 'error_desc' => 'Respons Sinta tidak valid.'], $res->status());
        } catch (\Throwable $e) {
            return response()->json(['error' => 1, 'error_desc' => 'Gagal hubungi Sinta: '.$e->getMessage()], 502);
        }
    }

    /** Sinkron publikasi Garuda: forward kata kunci ke endpoint Garuda kampus. */
    public function garuda(Request $request): JsonResponse
    {
        $validated = $request->validate(['query' => 'required|string|max:255']);

        $base = (string) Setting::get('garuda.base_url', '');

        if ($base === '') {
            return response()->json(['message' => 'Integrasi Garuda belum dikonfigurasi di halaman Integrasi (garuda.base_url).'], 422);
        }

        try {
            $res = Http::withToken((string) Setting::get('garuda.api_key', ''))
                ->timeout(30)
                ->post(rtrim($base, '/').'/publikasi', ['query' => $validated['query']]);

            return response()->json($res->json() ?? ['error' => 1, 'error_desc' => 'Respons Garuda tidak valid.'], $res->status());
        } catch (\Throwable $e) {
            return response()->json(['error' => 1, 'error_desc' => 'Gagal hubungi Garuda: '.$e->getMessage()], 502);
        }
    }

    /** TTE BSrE: ajukan tanda tangan elektronik untuk hash dokumen. */
    public function tte(Request $request): JsonResponse
    {
        $validated = $request->validate(['dokumen_hash' => 'required|string|max:255']);

        $base = (string) Setting::get('tte.base_url', '');

        if ($base === '') {
            return response()->json(['message' => 'Integrasi TTE BSrE belum dikonfigurasi di halaman Integrasi (tte.base_url).'], 422);
        }

        try {
            $res = Http::withBasicAuth(
                (string) Setting::get('tte.username', ''),
                (string) Setting::get('tte.password', '')
            )->timeout(30)->post(rtrim($base, '/').'/sign', ['hash' => $validated['dokumen_hash']]);

            return response()->json($res->json() ?? ['error' => 1, 'error_desc' => 'Respons TTE tidak valid.'], $res->status());
        } catch (\Throwable $e) {
            return response()->json(['error' => 1, 'error_desc' => 'Gagal hubungi layanan TTE: '.$e->getMessage()], 502);
        }
    }
}
