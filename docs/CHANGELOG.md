# Changelog

## 2026-11-12 — Lanjutan master audit (tanpa commit/push)
- Beasiswa (CRUD + penetapan + potongan otomatis generate tagihan), kalender akademik CRUD + agenda dashboard, yudisium bersyarat, helpdesk tiket + notifikasi, impor CSV + template, ekspor CSV tagihan/absensi, relasi model lengkap, FK 40+, 72 test hijau.

## 2026-11-12 — Hardening & finalisasi (master audit)
- IPK tertimbang SKS terpusat (`AkademikService`); KHS/transkrip tampilkan nama + total SKS + IP per tahun.
- Otorisasi: grup `role:*`, trait Livewire `RequiresRoles`, owner-check controller + mount, sidebar sadar peran.
- API: ability `mobile` + expiry 30 hari + throttle; login/PMB/surat/callback di-throttle; Sanctum limiter `api`.
- Keuangan: idempotency + lock, Xendit mapping benar, error gateway ramah, callback simulasi ditolak di prod.
- PMB daftar ulang: akun + role + NIM + tagihan PMB + guard email; berkas upload (ijazah/KK/foto).
- KRS: blokir revisi pasca-approve, cek prasyarat/bentrok/kuota.
- Upload bukti bayar manual + verifikasi; 14 view Livewire → Tabler; dashboard chart SVG + agenda + aktivitas; login split.
- Test: 62 hijau (termasuk `SecurityTest` 6 test). Docs: 12+5 file audit.

## 2026-11-12 — Sebelumnya
- Scaffold Laravel 13 + Tabler + Livewire + Spatie + Sanctum; PRD/PROGRESS; 5 agen paralel modul.
- SIAKAD penuh + API v1 + Flutter stub; ComfyUI/WAN 2.1; payment adapter (Tripay/Midtrans/Xendit/simulated) tanpa hardcode; FCM; PDDikti; DemoSeeder; Tes Koneksi.
