# Changelog

## 2026-11-13 — R2.1–R2.7 konsolidasi 20 agen (164 test hijau)
- Landing: stat Dosen/KRS% diperbaiki, 12 modul (tambah AI Chatbot, Alumni & Magang, Funnel PMB).
- R2.1: CRUD Dosen/Tendik, User/Role, profil, cuti/pindah prodi, KTM QR, TTE, kalender lock + .ics + /health + audit viewer.
- R2.2: kurikulum versi + ekivalensi + anti-siklus, jadwal anti-bentrok, pinjam ruang, presensi dosen/BAP, EDOM rekap, bobot nilai custom, remedial, wali list.
- R2.3: PMB CBT/wawancara + kuota guard, VA PMB, WA CRM, SEO prodi, referral, funnel.
- R2.4: UKT template + cicilan + denda + jurnal, QRIS/e-wallet, rekonsiliasi CSV, refund, WA H-7/H+7.
- R2.5: bank topik, plagiarisme check, Meilisearch fallback, versioning bimbingan, syarat sidang otomatis, booking visual, BA auto, MBKM + wisuda batch + alumni portal.
- R2.6: Flutter device-token + FCM topik, QR offline queue, KTM offline, IPK predictor, EN/ID.
- R2.7: AI chatbot/at-risk/rekomendasi/grading/anomali/notulen via Setting DB, API v2 + webhook HMAC, SSO skeleton, Feeder live-sync, R2/CDN.
- Fix silang: import KalenderService (Dashboard, Sidang/Pendaftaran), parse SuratController.

## 2026-11-12 — Landing + Flutter lengkap
- Landing Kampius full-mockup + section Mobile & Integrasi; Flutter lengkap (bottom nav 5 tab, 9 screen, VA, notifikasi, profil; `flutter analyze` bersih, 2 widget test hijau).

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
