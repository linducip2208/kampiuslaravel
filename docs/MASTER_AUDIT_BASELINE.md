# MASTER AUDIT BASELINE — Kampius ERP
Tanggal: 2026-11-13
Repo: linducip2208/kampiuslaravel | Branch: main

## Stack terverifikasi
- composer.json: php ^8.3, laravel/framework ^13.17, sanctum ^4.0, livewire ^4.4, spatie/permission ^8.5
- package.json: tailwindcss ^4.0, vite ^8.0, laravel-vite-plugin ^3.1
- Frontend: Tabler via CDN (layouts/tabler.blade.php) + Tailwind v4 (resources/css/app.css)
- DB: guarded via Schema::hasTable di DashboardController & welcome route

## Rute dashboard
- GET /dashboard -> DashboardController@index (auth)
- GET /dashboard/pimpinan -> DashboardController@pimpinan (role pimpinan|superadmin)

## Data dashboard baru (real queries, no fake)
- stats: mahasiswa, mahasiswa_aktif, mahasiswa_baru, dosen_aktif, prodi, matakuliah, krs_pending, pmb_pending, verifikasi_pending, outstanding, pendapatan_bulan_ini, pendapatan_total
- chartNominal/chartTagihan: 6 bulan (pembayaran verified vs tagihan nominal)
- perfLabels/perfValues: approval rate KRS per 8 TahunAkademik terakhir
- angkatanBreakdown, statusBreakdown, messages (pengumuman), aktivitasMahasiswa (KRS terbaru), agenda, aktivitas (audit_logs)

## Safety
- Tidak ada migrate:fresh/db:wipe, tidak ada truncate, tidak ada commit/push.
- Semua query baru memakai guard Schema::hasTable agar aman bila tabel belum ada.
- Authorization tetap server-side (middleware role + @can di blade).
