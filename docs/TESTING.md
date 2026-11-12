# Testing

`php artisan test --compact` — PHPUnit 12, RefreshDatabase, Http::fake untuk gateway/FCM/Feeder.

## Cakupan (62 test, 194 assertions — hijau)
- Auth web (login/logout, tamu redirect) + API auth (gagal/berhasil/token/logout).
- API guard (401 tanpa token, ability `mobile`).
- PMB (daftar, validasi, cek, verifikasi, daftar ulang), KRS (aju, blokir SKS/tunggakan, approve, cetak, revisi-terkunci, prasyarat/bentrok/kuota).
- LMS (materi/tugas), absensi QR (valid/expired), nilai (NA/huruf/kunci).
- Skripsi end-to-end, keuangan (generate, verifikasi, VA, callback, settle, status).
- ComfyUI (workflow inject, generate fake, halaman publik), integrasi (driver map, unknown driver, Xendit/Tripay fake, kredensial-kosong 422, Tes Koneksi).
- PDDikti export CSV, notifikasi broadcast + API, API VA mobile.
- **SecurityTest** (6): IDOR KHS/transkrip, tanpa-role 403, dosen vs keuangan, keuangan vs pimpinan, tamu redirect.

## Menjalankan
```bash
php artisan test --compact            # semua
php artisan test --compact --filter=Nilai
vendor/bin/pint --format agent        # gaya kode (wajib hijau)
```
