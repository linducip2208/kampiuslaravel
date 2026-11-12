# Keamanan

## Diterapkan
- Auth session (regenerate + invalidate saat login/logout), throttle login (`login:10/mnt`), PMB/surat publik (`public:60/mnt`), callback (`30/mnt`), API (`api:120/mnt`).
- RBAC route group + `RequiresRoles` di 22 komponen Livewire + owner-check controller + owner-scope mount.
- CSRF aktif global kecuali `payment/callback` (gateway tanpa session; keaslian via HMAC/signature adapter; driver `simulated` ditolak di production).
- Sanctum: token ability `mobile` ditegakkan + expiry 30 hari.
- Upload: `mimes` + batas ukuran, disk publik hanya file terdaftar, nama acak.
- Finansial: transaction + `lockForUpdate` + idempotency (status `pending` saja diproses); Xendit mapping status diperbaiki.
- Password `hashed` cast; error gateway diringkas ke user (detail di log); kredensial demo dihapus dari view login.

## Sisa risiko (jujur)
1. `APP_DEBUG=true` di `.env` dev — pastikan `false` + `APP_ENV=production` di produksi.
2. Integrasi eksternal (Tripay/FCM/Feeder/ComfyUI) butuh kredensial asli; Tes Koneksi tersedia di halaman Integrasi.
3. File upload publik: pertimbangkan antivirus + pindah ke disk privat bila kebijakan kampus menuntut.
4. Rate-limit default longgar untuk demo; ketatkan di WAF/proxy produksi.
5. Audit trail mencakup aksi utama; peluasan cakupan bertahap (lihat `AuditLog`).

Detail temuan awal: `AUDIT_SECURITY.md`.
