# Arsitektur — SIMAK Kuliah

Single-campus ERP. Satu Laravel monolith: Blade + Livewire (web), Sanctum API v1 (Flutter), MySQL 8, Redis opsional (queue default database).

## Lapisan
- **Web** (`routes/web.php`, 62 route): publik (pSEO + PMB + verifikasi surat) tanpa auth; modul auth dengan grup `role:*` Spatie; dokumen cetak dengan otorisasi kepemilikan server-side (`AuthorizesMahasiswaData`).
- **API** (`routes/api.php`, 17 endpoint, prefix v1): Sanctum Bearer + ability `mobile` + throttle `api` + expiry token 30 hari.
- **Domain**: `AkademikService` (IPK tertimbang SKS, batas SKS), `KeuanganService` (tagihan + verifikasi idempoten + lock), `PaymentService` + adapter (`simulated/tripay/midtrans/xendit`, resolve via `config/payment.php`), `NotifikasiService` (inbox + FCM), `PddiktiService` (CSV + Feeder), `ComfyUIService` (generate gambar), `SuratNomorService`.
- **Async**: `GenerateMediaJob` (queue database). **Audit**: `AuditLog` di aksi sensitif.
- **Upload**: disk `public`, validasi `mimes` + batas ukuran, nama file acak Laravel.

## Aturan keras
1. Sembunyikan tombol BUKAN otorisasi — setiap aksi sensitif cek role di route + Livewire (`RequiresRoles`) + ownership di controller.
2. Uang: semua mutasi finansial dalam DB transaction + row lock + idempotency (cek status `pending`).
3. IPK = Σ(bobot×SKS)/Σ(SKS) — satu-satunya sumber: `AkademikService::ringkasan()`.
4. Gateway baru = 1 adapter + 1 baris `config/payment.php`. Kredensial hanya via tabel `settings` (halaman Integrasi).
5. Tanpa `migrate:fresh` di data berisi; perubahan skema selalu forward migration.
