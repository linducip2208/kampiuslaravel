# API_DOCUMENTATION (ringkas; detail pola: docs/API.md)

Base URL: `{APP_URL}/api`. Semua respons JSON. Error standar:
401 tanpa token, 403 bukan pemilik/peran, 422 validasi/gagal aturan bisnis,
429 throttle, 503 integrasi belum dikonfigurasi.

## v1 — mobile (auth:sanctum, ability `mobile`)
- POST /v1/login {email,password} → {token} (throttle 10,1).
- GET /v1/me · POST /v1/logout · POST /v1/device-token.
- GET /v1/jadwal-hari-ini · GET /v1/krs-aktif · GET /v1/nilai-ringkas (owner-scope).
- POST /v1/absensi/scan · GET /v1/tugas · POST /v1/tugas/{id}/kumpul (file, max 10MB, owner wajib).
- GET /v1/tagihan (ringkasan total/terbayar/menunggak) · POST /v1/tagihan/{id}/va (422 bila lunas).
- GET|POST /v1/bimbingan (owner-scope).
- GET /v1/pengumuman · GET /v1/notifikasi · POST /v1/notifikasi/{id}/read · POST /v1/notifikasi/token.
- POST /v1/ai/chat, GET /v1/ai/rekomendasi, POST /v1/ai/grade|ringkas|notulen, GET /v1/analitik/* (throttle 30,1; butuh kredensial provider, tanpa itu nonaktif aman).

## v2 — publik (throttle 60,1, tanpa token)
- GET /v2/status · GET /v2/prodi (paginasi) · GET /v2/pengumuman (hanya published).
- POST /v2/webhook/terima — header `X-Webhook-Signature: HMAC-SHA256(raw_body, webhook.secret)`; 503 bila secret <16 char; 400 bila signature salah. Teruji di IntegrasiTest.

## Webhook keluar / callback masuk
- POST /payment/callback — signature diteruskan ke adapter (Tripay HMAC body); tolak nominal mismatch (400); idempoten via `settleByReference` (verified ganda → false).
- VA PMB (`PMBVA-*`) melunasi pendaftar, bukan tagihan.

## Aturan kompatibilitas
- Jangan ubah kontrak v1 tanpa versi baru; tambah field opsional saja. Daftar rute: `php artisan route:list --path=api`.
