<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\Nilai;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Klien Feeder PDDikti + export CSV.
 * Kredensial & URL dari halaman admin Integrasi.
 */
class PddiktiService
{
    private string $base;

    public function __construct()
    {
        $this->base = rtrim((string) Setting::get('pddikti.base_url', 'http://feeder.example.ac.id:8082/ws/live2.php'), '/');
    }

    public function token(): ?string
    {
        $res = Http::timeout(30)->post($this->base, [
            'act' => 'GetToken',
            'username' => Setting::get('pddikti.username', ''),
            'password' => Setting::get('pddikti.password', ''),
        ]);

        return $res->successful() ? $res->json('data.token', $res->json('token')) : null;
    }

    public function push(string $act, array $record): array
    {
        $token = $this->token();

        if (! $token) {
            return ['error' => 1, 'error_desc' => 'Gagal login Feeder — cek kredensial di halaman Integrasi.'];
        }

        $res = Http::timeout(60)->post($this->base, [
            'act' => $act,
            'token' => $token,
            'record' => $record,
        ]);

        return $res->json() ?? ['error' => 1, 'error_desc' => 'Respons Feeder tidak valid.'];
    }

    /** Export CSV siap-feeder: mahasiswa | nilai | kelulusan. Return path storage. */
    public function exportCsv(string $jenis): string
    {
        $rows = match ($jenis) {
            'nilai' => $this->rowsNilai(),
            'kelulusan' => $this->rowsKelulusan(),
            default => $this->rowsMahasiswa(),
        };

        $path = "exports/pddikti-{$jenis}-".now()->format('Ymd-His').'.csv';
        Storage::makeDirectory('exports');
        $handle = fopen(Storage::path($path), 'w');

        if (! $handle) {
            throw new \RuntimeException('Gagal membuat file export: '.$path);
        }

        foreach ($rows as $row) {
            fputcsv($handle, $row, ';');
        }
        fclose($handle);

        return $path;
    }

    private function rowsMahasiswa(): array
    {
        $rows = [['nim', 'nama', 'prodi_kode', 'angkatan', 'status']];

        Mahasiswa::with(['user', 'prodi'])->chunk(500, function ($chunk) use (&$rows) {
            foreach ($chunk as $m) {
                $rows[] = [$m->nim, $m->user->name ?? '', $m->prodi->kode ?? '', $m->angkatan, $m->status];
            }
        });

        return $rows;
    }

    private function rowsNilai(): array
    {
        $rows = [['nim', 'kode_mk', 'huruf', 'bobot']];

        Nilai::with('mahasiswa', 'kelas.matakuliah')->chunk(500, function ($chunk) use (&$rows) {
            foreach ($chunk as $n) {
                $rows[] = [$n->mahasiswa->nim ?? '', $n->kelas->matakuliah->kode ?? '', $n->huruf, $n->bobot];
            }
        });

        return $rows;
    }

    private function rowsKelulusan(): array
    {
        $rows = [['nim', 'nama', 'prodi_kode', 'tanggal_lulus']];

        Mahasiswa::where('status', 'lulus')->with(['user', 'prodi'])->chunk(500, function ($chunk) use (&$rows) {
            foreach ($chunk as $m) {
                $rows[] = [$m->nim, $m->user->name ?? '', $m->prodi->kode ?? '', $m->updated_at->format('Y-m-d')];
            }
        });

        return $rows;
    }
}
