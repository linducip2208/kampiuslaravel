# Integrasi — Payment VA, Push (FCM), Feeder PDDikti

Semua kredensial diisi lewat halaman **Integrasi** (sidebar, tabel `settings`) —
tidak ada yang di-hardcode di kode maupun `.env`.

## 1. Payment VA — TANPA hardcode

Arsitektur: `PaymentGatewayInterface` + `config/payment.php` (peta `drivers`)
+ `PaymentService` (resolve driver dari settings). **Gateway baru = 1 class
adapter + 1 baris di config** — tidak ada daftar driver di kode bisnis,
tidak ada URL/key/prefix di kode (semua dari settings, fallback dari config).

Driver bawaan: `simulated` (dev), **`tripay`** (dipakai — VA / gerai / QRIS),
`midtrans` (Core API bank-transfer VA), `xendit` (callback_virtual_accounts).
Dropdown + validasi halaman admin dibaca dari config (otomatis ikut bila nambah driver).

Alur mahasiswa: **Keuangan → Bayar Tagihan** (`/keuangan/bayar`) → Buat VA →
`POST /keuangan/bayar/{tagihan}/va` → VA + reference. Daftar VA aktif bisa
dicek statusnya (`GET /keuangan/bayar/status/{pembayaran}` → `syncStatus()`).
Callback gateway: `POST /payment/callback` — dikecualikan CSRF di
`bootstrap/app.php` (gateway tidak punya session), keaslian diverifikasi via
signature adapter (Midtrans sha512 / Xendit callback token), lalu
`settleByReference` melunasi tagihan → membuka blokir KRS/sidang.

Aktifkan Tripay (dipakai kampus ini):
1. Daftar di tripay.co.id → ambil **kode merchant, API key, private key**
   (sandbox dulu: `https://tripay.co.id/api-sandbox/`).
2. Halaman Integrasi → driver `tripay` → isi merchant code, API key
   (kolom server key), private key, base URL sandbox/prod, channel
   (mis. `BRIVA`, `BNIVA`, `QRIS`).
3. Daftarkan callback URL `https://domain-kamu/payment/callback` di dashboard
   Tripay (keamanan via HMAC `X-Callback-Signature`).
4. Uji end-to-end di sandbox → ganti base URL ke
   `https://tripay.co.id/api/` untuk produksi.

## 2. Push notification (`fcm.*`)

Setiap pengiriman selalu tersimpan di tabel `notifikasi` (inbox web + API mobile
`GET /api/v1/notifikasi`, tandai baca `POST /{id}/read`, daftar device
`POST /notifikasi/token`). Penerusan FCM HTTP v1 hanya jalan bila Project ID +
path service-account JSON terisi; selain itu dicatat di log (tidak error).

Aktifkan FCM: Firebase Console → Project settings → Service accounts →
Generate key → simpan JSON di server (mis. `/secure/firebase-adminsdk.json`,
JANGAN di public/) → isi di halaman Integrasi. Mobile (`mobile/`) kirim
`fcm_token` device ke endpoint token setiap login.

Broadcast admin: halaman **Notifikasi → Broadcast** (`/notifikasi/broadcast`).

## 3. Feeder PDDikti (`pddikti.*`)

Halaman **PDDikti** (`/pddikti`): download CSV `mahasiswa | nilai | kelulusan`
(delimiter `;`, siap mapping Neo Feeder) + push record langsung
(`POST /pddikti/push {act, record}` memakai token `GetToken`).

Isi base URL + username + password Feeder kampus di halaman Integrasi.
Perintah terkait: tidak ada cron bawaan — jalankan export berkala sesuai
jadwal pelaporan (disarankan tiap akhir semester + setelah yudisium).

### 3a. Feeder live-sync (per-record)

Aktifkan di halaman Integrasi → Feeder PDDikti → Live-sync = Aktif.
Endpoint (role `pimpinan|superadmin`):

- `GET /pddikti/status` → `{live, base_url_set, acts}` (tanpa panggil jaringan).
- `POST /pddikti/sync {tabel: mahasiswa|nilai|kelulusan, record: {...}}`
  (throttle 30/menit) → map ke act (`InsertMhs|InsertNilai|InsertKelulusan`)
  lalu push via token `GetToken`. Gagal login = cek kredensial di Integrasi.

Pola produksi: panggil `/pddikti/sync` dari observer/job setelah KRS
disetujui / nilai dikunci / yudisium (queue `database`, retry 3x), export CSV
tetap sebagai fallback rekonsiliasi.

## 4. SSO Google & Microsoft

Kredensial diisi di halaman **Integrasi → SSO** (settings
`sso.google_client_id/secret`, `sso.microsoft_client_id/secret`);
fallback env `GOOGLE_*` / `MICROSOFT_*` via `config/services.php`.
Tombol "Tes" menampilkan redirect URI yang wajib didaftarkan di konsol
provider (`{APP_URL}/auth/{google,microsoft}/callback`).

Aktivasi penuh (1x, sentuh modul auth saja):

1. `composer require laravel/socialite`
2. Route `GET /auth/{provider}` + `/callback`: cari user by email,
   buat/link akun, `Auth::login()` (tolak bila email tak dikenal bila
   kampus menerapkan allowlist).
3. Daftarkan Client ID/Secret + redirect URI di Google Cloud Console
   (OAuth client) dan Entra ID (App registration).

Tanpa Socialite terpasang, login password tetap berfungsi — halaman
Integrasi hanya menyimpan & memvalidasi kredensial.

## 5. Sinta & Garuda (sinkron riset)

Kredensial di halaman **Integrasi → Riset** (`sinta.base_url/api_key`,
`garuda.base_url/api_key`). Endpoint (role `pimpinan|superadmin`,
throttle 30/menit):

- `POST /pddikti/sinta {nidn}` → forward ke `{sinta.base_url}/profil`
  (bearer `sinta.api_key`).
- `POST /pddikti/garuda {query}` → forward ke `{garuda.base_url}/publikasi`.

Belum dikonfigurasi → `422` dengan pesan "Integrasi … belum
dikonfigurasi". Semua forward memakai `Http::timeout(30)` sehingga
mudah di-fake di test.

## 6. API publik v2 + webhook

Read-only publik (tanpa token, `throttle:60,1`):

- `GET /api/v2/status` — health versi API.
- `GET /api/v2/prodi` — paginasi `kode,nama,jenjang,slug`.
- `GET /api/v2/pengumuman` — hanya yang `published_at` terisi.

Autentikasi mobile tetap di `v1` (Sanctum `abilities:mobile`) — v2 tidak
mengubah kontrak mobile yang sudah berjalan.

Webhook masuk: `POST /api/v2/webhook/terima` (`throttle:30,1`).
Verifikasi `X-Webhook-Signature = HMAC-SHA256(raw_body, settings
webhook.secret)`; secret min. 16 karakter, diisi di halaman Integrasi.
Belum diisi → `503`; signature salah → `400`; event valid dicatat di log
(`webhook.v2.received`). Contoh kirim:

```bash
SIG=$(printf '{"event":"pmb.baru","payload":{}}' | openssl dgst -sha256 -hmac "$SECRET")
curl -X POST /api/v2/webhook/terima -H "X-Webhook-Signature: $SIG" \
  -H 'Content-Type: application/json' -d '{"event":"pmb.baru","payload":{}}'
```

## 7. TTE BSrE

Kredensial di halaman **Integrasi → TTE BSrE** (`tte.base_url/username/
password`). Endpoint `POST /pddikti/tte {dokumen_hash}` (role
`pimpinan|superadmin`) → forward basic-auth ke `{tte.base_url}/sign`.
Alur dokumen: surat disetujui → hash (SHA-256 PDF final) → `/pddikti/tte`
→ simpan token/serial balikan ke nomor surat. Belum dikonfigurasi → `422`.

## 8. Storage S3/R2 + CDN

Disk di `config/filesystems.php`: `local` (default), `public`,
`s3` (AWS, env `AWS_*`), **`r2`** (Cloudflare R2, S3-compatible, env
`R2_*`), plus `cdn_url` (env `CDN_URL`, override via settings
`storage.cdn_url`).

- Key/secret **tetap di `.env`** (`R2_ACCESS_KEY_ID` dkk) — tidak masuk DB.
- Endpoint+bucket+CDN URL di halaman **Integrasi → Storage**.
- Pakai: `FILESYSTEM_DISK=r2` (atau `s3`) untuk upload dokumen/cover;
  sajikan via CDN: `rtrim(config('filesystems.cdn_url') ?? config('app.url'), '/').'/storage/'.$path`.
- Tes koneksi di halaman Integrasi memvalidasi kehadiran konfigurasi
  (tidak mengunggah file).

## 9. Halaman Integrasi — kartu baru

Selain Payment/FCM/Feeder/ComfyUI: **SSO**, **Sinta & Garuda**,
**TTE BSrE**, **Storage R2/S3+CDN**, **API Publik v2 & Webhook**,
**Multi-tenant (sketsa)** — masing-masing dengan tombol tes yang memberi
pesan OK vs "belum dikonfigurasi" (tidak throw). Semua tersimpan di tabel
`settings` via `Setting::set`, tidak ada hardcode.

## 10. Multi-tenant (sketsa — BELUM aktif)

Mode di halaman Integrasi → `tenant.mode`: `single` (saat ini) atau
`sketsa_multi`. Sketsa bila kampus membuka cabang:

1. Tambah `tenant_id` (FK `tenants`) ke tabel transaksional + global scope
   `TenantScope` per model (bypass untuk `superadmin` lintas-tenant).
2. Middleware pemilih tenant dari subdomain/domain (`kampusA.ac.id`),
   simpan di `app()->instance('tenant')` + `config(['database...'])` bila
   database-per-tenant (atau satu DB + scope bila shared-schema).
3. Seeder per-tenant untuk roles/permissions (spatie) + user admin tenant.
4. JANGAN aktifkan sebelum migrasi data + audit `AuditLog` per-tenant.

Sampai disetujui, biarkan `tenant.mode = single` — tidak ada kode bisnis
yang membaca mode ini hari ini.

## 11. Status Wave D — matriks kesiapan jujur (2026-11-15)

Kode adapter selesai dan teruji; yang belum ada adalah **kredensial/
konektivitas produksi** (nol kredensial di repo, sesuai aturan). Bukti
otomatis: `tests/Feature/WaveDReadinessTest.php`.

| Item | Adapter (kode) | Bukti tes | Kurang (eksternal) | Status |
|---|---|---|---|---|
| M-061 Feeder | `PddiktiService` + tes koneksi + `pddikti.sync` | IntegrasiTest, readiness | base URL + user/pass Feeder | BLOCKED |
| M-062 gateway prod | pola driver `config/payment.php` + guard simulasi-ditolak-di-prod | WaveACriticalTest | merchant key prod + callback URL publik | BLOCKED |
| M-063 WA | `WaService` (simulasi bila gateway kosong) | readiness (simulasi true) | gateway_url + token | BLOCKED |
| M-064 Email/FCM | FCM token + `MailService` + `TagihanReminderMail` (guard SMTP) | kontrak mobile + readiness | SMTP host/kredensial, service-account FCM | SELESAI (kode), kirim nyata butuh kredensial |
| M-065 SSO | dokumen aktivasi §4 (Socialite belum dipasang) | — | Client ID/Secret Google+Entra | BLOCKED |
| M-066 TTE | forward BSrE `pddikti/tte` | readiness (rute ada) | base URL + user/pass BSrE | BLOCKED |

Channel email ikut idempotensi notif H-7/H+7 yang sama (tanpa spam);
hanya jalan bila `MAIL_MAILER=smtp` + host terisi + user punya email.
