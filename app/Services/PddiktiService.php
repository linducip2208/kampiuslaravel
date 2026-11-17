<?php

namespace App\Services;

use App\Models\AuditLog;
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
        $this->ensureSafeBaseUrl();

        $res = Http::timeout(30)->post($this->base, [
            'act' => 'GetToken',
            'username' => Setting::get('pddikti.username', ''),
            'password' => Setting::get('pddikti.password', ''),
        ]);

        return $res->successful() ? $res->json('data.token', $res->json('token')) : null;
    }

    public function push(string $act, array $record): array
    {
        $this->ensureSafeBaseUrl();

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

    /**
     * Guard SSRF: tolak base_url dengan host kosong atau — bila
     * Setting pddikti.guard_ssrf truthy — yang resolve ke IP
     * privat/reserved/loopback. Default OFF agar host dummy pada
     * pengujian (Http::fake) tetap lolos tanpa DNS nyata.
     */
    private function ensureSafeBaseUrl(): void
    {
        $this->base = rtrim((string) Setting::get('pddikti.base_url', 'http://feeder.example.ac.id:8082/ws/live2.php'), '/');

        $host = (string) parse_url($this->base, PHP_URL_HOST);

        if ($host === '') {
            throw new \RuntimeException('URL Feeder tidak valid — host base_url kosong. Periksa pengaturan pddikti.base_url di halaman Integrasi.');
        }

        if (! filter_var(Setting::get('pddikti.guard_ssrf', '0'), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $ips = [];

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $ips[] = $host;
        } else {
            $records = @dns_get_record($host, DNS_A + DNS_AAAA);

            if (is_array($records)) {
                foreach ($records as $rec) {
                    if (! empty($rec['ip'])) {
                        $ips[] = $rec['ip'];
                    }

                    if (! empty($rec['ipv6'])) {
                        $ips[] = $rec['ipv6'];
                    }
                }
            }

            $resolved = @gethostbyname($host);

            if ($resolved !== $host) {
                $ips[] = $resolved;
            }
        }

        foreach (array_unique($ips) as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                try {
                    AuditLog::create([
                        'user_id' => auth()->id(),
                        'action' => 'pddikti.ssrf_blocked',
                        'model' => 'pddikti',
                        'model_id' => 0,
                        'diff_json' => ['host' => $host, 'ip' => $ip, 'base_url' => $this->base],
                        'created_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    // Pencatatan audit tidak boleh menggagalkan proteksi.
                }

                throw new \RuntimeException("URL Feeder diblokir — host {$host} mengarah ke alamat privat/reserved ({$ip}). Periksa pengaturan pddikti.base_url di halaman Integrasi.");
            }
        }
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
