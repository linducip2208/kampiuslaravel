<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Notifikasi;
use App\Models\NotifLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Push notification: selalu simpan ke DB (inbox + API mobile),
 * teruskan ke FCM HTTP v1 bila service-account dikonfigurasi admin.
 */
class NotifikasiService
{
    public function send(User $user, string $judul, string $isi): Notifikasi
    {
        $notif = Notifikasi::create(['user_id' => $user->id, 'judul' => $judul, 'isi' => $isi]);

        try {
            NotifLog::create(['notifikasi_id' => $notif->id, 'user_id' => $user->id, 'channel' => 'db', 'status' => 'terkirim']);
        } catch (\Throwable) {
        }

        $this->pushFcm($user, $judul, $isi, $notif->id);

        return $notif;
    }

    /** Broadcast ke semua user dengan role tertentu. Return jumlah terkirim. */
    public function broadcast(string $role, string $judul, string $isi): int
    {
        $count = 0;

        User::role($role)->chunk(100, function ($users) use ($judul, $isi, &$count) {
            foreach ($users as $user) {
                $this->send($user, $judul, $isi);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Broadcast ke topik FCM prodi (`prodi_{id}`) — satu request untuk
     * semua device ber-topic, tanpa iterasi user. Tanpa kredensial:
     * tercatat 'dilewati', return false (inbox tetap jalan via broadcast()).
     */
    public function broadcastTopik(int $prodiId, string $judul, string $isi): bool
    {
        $projectId = Setting::get('fcm.project_id', '');
        $serviceAccount = Setting::get('fcm.service_account_path', '');

        if ($projectId === '' || $serviceAccount === '' || ! is_file($serviceAccount)) {
            Log::info('FCM topik dilewati (belum dikonfigurasi)', ['topic' => "prodi_{$prodiId}"]);

            $this->catatAuditTopik($prodiId, $judul, 'dilewati', 'tanpa kredensial');

            return false;
        }

        $accessToken = $this->googleAccessToken($serviceAccount);

        if (! $accessToken) {
            $this->catatAuditTopik($prodiId, $judul, 'dilewati', 'token google gagal');

            return false;
        }

        $ok = $this->kirimPesanTopik($projectId, $accessToken, $prodiId, $judul, $isi);

        $this->catatAuditTopik($prodiId, $judul, $ok ? 'terkirim' : 'gagal', null);

        return $ok;
    }

    /** POST pesan ke topik FCM (dipisah agar dapat diuji tanpa kredensial). */
    public function kirimPesanTopik(string $projectId, string $accessToken, int $prodiId, string $judul, string $isi): bool
    {
        $res = Http::withToken($accessToken)->timeout(15)->post(
            "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
            ['message' => ['topic' => "prodi_{$prodiId}", 'notification' => ['title' => $judul, 'body' => $isi]]]
        );

        return $res->successful();
    }

    private function catatAuditTopik(int $prodiId, string $judul, string $hasil, ?string $keterangan): void
    {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'notifikasi.broadcast_topik',
                'model' => null,
                'model_id' => null,
                'diff_json' => ['prodi_id' => $prodiId, 'topic' => "prodi_{$prodiId}", 'judul' => $judul, 'hasil' => $hasil, 'keterangan' => $keterangan],
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
        }
    }

    private function pushFcm(User $user, string $judul, string $isi, ?int $notifikasiId = null): void
    {
        $projectId = Setting::get('fcm.project_id', '');
        $serviceAccount = Setting::get('fcm.service_account_path', '');
        $deviceToken = $user->getAttribute('fcm_token');

        if ($projectId === '' || $serviceAccount === '' || ! is_file($serviceAccount) || ! $deviceToken) {
            Log::info('FCM skipped (belum dikonfigurasi / tanpa device token)', ['user_id' => $user->id]);

            try {
                NotifLog::create(['notifikasi_id' => $notifikasiId, 'user_id' => $user->id, 'channel' => 'fcm', 'status' => 'dilewati', 'keterangan' => 'tanpa konfigurasi/device token']);
            } catch (\Throwable) {
            }

            return;
        }

        $accessToken = $this->googleAccessToken($serviceAccount);

        if (! $accessToken) {
            return;
        }

        Http::withToken($accessToken)->timeout(15)->post(
            "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
            ['message' => ['token' => $deviceToken, 'notification' => ['title' => $judul, 'body' => $isi]]]
        );

        try {
            NotifLog::create(['notifikasi_id' => $notifikasiId, 'user_id' => $user->id, 'channel' => 'fcm', 'status' => 'terkirim']);
        } catch (\Throwable) {
        }
    }

    private function googleAccessToken(string $serviceAccountPath): ?string
    {
        $json = json_decode((string) file_get_contents($serviceAccountPath), true);

        if (! is_array($json)) {
            return null;
        }

        $jwt = $this->signedJwt($json);

        if (! $jwt) {
            return null;
        }

        $res = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        return $res->successful() ? $res->json('access_token') : null;
    }

    private function signedJwt(array $json): ?string
    {
        if (empty($json['private_key']) || empty($json['client_email'])) {
            return null;
        }

        $header = rtrim(strtr(base64_encode('{"alg":"RS256","typ":"JWT"}'), '+/', '-_'), '=');
        $now = time();
        $claims = rtrim(strtr(base64_encode(json_encode([
            'iss' => $json['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ])), '+/', '-_'), '=');

        $key = openssl_pkey_get_private($json['private_key']);

        if (! $key || ! openssl_sign("{$header}.{$claims}", $sig, $key, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        return $header.'.'.$claims.'.'.rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
    }
}
