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
