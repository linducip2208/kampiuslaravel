<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NilaiResource;
use App\Http\Resources\TagihanResource;
use App\Models\Mahasiswa;
use App\Models\Nilai;
use App\Models\Tagihan;
use App\Models\WaliMahasiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ide bank: API orangtua read-only — nilai + tagihan anak yang ditautkan.
 * Tanpa tautan → 403; hanya data anak sendiri (owner-scope via pivot).
 */
class WaliController extends Controller
{
    /** @return list<int> */
    private function anakIds(Request $request): array
    {
        return WaliMahasiswa::where('user_id', $request->user()->id)->pluck('mahasiswa_id')->all();
    }

    private function anakTertaut(Request $request, int $mahasiswaId): ?Mahasiswa
    {
        if (! in_array($mahasiswaId, $this->anakIds($request), true)) {
            return null;
        }

        return Mahasiswa::with('user')->find($mahasiswaId);
    }

    public function anak(Request $request): JsonResponse
    {
        $ids = $this->anakIds($request);

        $data = Mahasiswa::with('user')->whereIn('id', $ids)->get()->map(fn ($m) => [
            'id' => $m->id,
            'nim' => $m->nim,
            'nama' => $m->user->name ?? null,
            'status' => $m->status,
        ]);

        return response()->json(['data' => $data]);
    }

    public function nilai(Request $request, int $mahasiswa): JsonResponse
    {
        $mhs = $this->anakTertaut($request, $mahasiswa);

        if (! $mhs) {
            return response()->json(['message' => 'Bukan anak yang ditautkan.'], 403);
        }

        $nilai = Nilai::with(['kelas.matakuliah'])->where('mahasiswa_id', $mhs->id)->where('is_locked', true)->get();

        return response()->json(['data' => NilaiResource::collection($nilai)]);
    }

    public function tagihan(Request $request, int $mahasiswa): JsonResponse
    {
        $mhs = $this->anakTertaut($request, $mahasiswa);

        if (! $mhs) {
            return response()->json(['message' => 'Bukan anak yang ditautkan.'], 403);
        }

        $tagihan = Tagihan::with('tahunAkademik')->where('mahasiswa_id', $mhs->id)->latest('id')->get();

        return response()->json([
            'data' => TagihanResource::collection($tagihan),
            'ringkasan' => [
                'total_tagihan' => (float) $tagihan->sum(fn ($t) => $t->totalTagihan()),
                'total_terbayar' => (float) $tagihan->sum('terbayar'),
            ],
        ]);
    }
}
