# API — KAMPUS
- v1 (auth:sanctum, abilities:mobile): /me, jadwal-hari-ini, krs-aktif, nilai-ringkas, absensi/scan, tugas, tagihan (+VA), bimbingan, pengumuman, notifikasi, ai/chat, device-token.
- v2 publik (throttle 60,1): /status, /prodi, /pengumuman. Webhook: /webhook/terima (HMAC X-Webhook-Signature, throttle 30,1).
- Kontrak dipertahankan. Perbaikan: Tagihan index eager load; createVa tolak lunas (422); Tugas index owner-scope.

## Contoh
- Login: POST /api/v1/login {email,password} -> {token} (throttle 10,1).
- Tagihan: GET /api/v1/tagihan (Bearer) -> {data[], ringkasan{total_tagihan,total_terbayar,total_menunggak}}.
- VA: POST /api/v1/tagihan/{id}/va {method} -> {reference,va_number,qr_payload,expires_at} atau 422 bila lunas.
- Callback: POST /payment/callback {reference,amount,...} + header signature -> {ok:true}; nominal mismatch -> 400.
- Error standar: 401 tanpa token, 403 bukan pemilik, 422 validasi/lunas, 429 throttle.
