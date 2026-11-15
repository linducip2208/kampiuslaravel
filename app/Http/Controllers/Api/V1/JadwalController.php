<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\JadwalResource;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Mahasiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class JadwalController extends Controller
{
    public function hariIni(Request $request): JsonResponse
    {
        $hari = $this->hariIndonesia(Carbon::now('Asia/Jakarta'));

        if (! class_exists(Kelas::class)) {
            return response()->json(['data' => []]);
        }

        try {
            $kelasIds = $this->kelasIdsMahasiswa($request);
            $query = Kelas::query()->where('hari', $hari);

            if ($kelasIds !== null) {
                $query->whereIn('id', $kelasIds);
            } else {
                // M-025/P5: tanpa scope mahasiswa, batasi per peran agar tak unbounded.
                // Dosen: hanya kelasnya sendiri. Peran lain: 200 baris terbaru
                // (bentuk respons tetap array agar kontrak mobile tidak pecah).
                $dosenId = class_exists(Dosen::class)
                    ? Dosen::where('user_id', $request->user()->id)->value('id')
                    : null;

                if ($dosenId) {
                    $query->where('dosen_id', $dosenId);
                } else {
                    $query->limit(200);
                }
            }

            $jadwal = $query->with(['matakuliah', 'dosen', 'ruang'])->orderBy('jam_mulai')->get();
        } catch (\Throwable) {
            return response()->json(['data' => []]);
        }

        return response()->json(['data' => JadwalResource::collection($jadwal)]);
    }

    /**
     * @return array<int>|null null = tampilkan semua (bukan mahasiswa / relasi belum ada)
     */
    private function kelasIdsMahasiswa(Request $request): ?array
    {
        if (! class_exists(Mahasiswa::class) || ! class_exists(Krs::class)) {
            return null;
        }

        try {
            $mhs = Mahasiswa::where('user_id', $request->user()->id)->first();

            if (! $mhs) {
                return null;
            }

            $krs = Krs::where('mahasiswa_id', $mhs->id)
                ->whereIn('status', ['diajukan', 'disetujui'])
                ->latest('id')
                ->first();

            if (! $krs || ! method_exists($krs, 'details')) {
                return null;
            }

            return $krs->details()->pluck('kelas_id')->all() ?: [];
        } catch (\Throwable) {
            return null;
        }
    }

    private function hariIndonesia(Carbon $now): string
    {
        return match ($now->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => 'Minggu',
        };
    }
}
