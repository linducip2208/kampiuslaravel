# UI/UX BEFORE → AFTER (bukti struktural)

Tanggal: 2026-11-14. Tidak ada screenshot browser (tidak ada tooling
otomasi browser di lingkungan ini); perbandingan di bawah merujuk pada
file aktual dan dapat diverifikasi dengan membuka rute terkait.

## 1. Landing (`resources/views/welcome.blade.php`, `/`)
- SEBELUM: satu hero generik + strip fitur + daftar modul + peran.
- SESUDAH: 11 seksi sesuai spesifikasi — navbar premium, hero 2 kolom
  (badge + headline + 2 CTA + indikator kapabilitas real + mockup
  dashboard ERP besar berlapis), value props, 12 kartu modul (semua
  menuju rute modul asli), 2 komposisi pratinjau produk (keuangan &
  alur KRS dengan angka real), lifecycle 9 tahap, 6 kartu peran,
  keamanan+integrasi terverifikasi, FAQ 5 item, CTA, footer.
- Bukti: tidak ada testimoni/angka fiktif; semua angka dari `$stats`.

## 2. Shell (`resources/views/layouts/tabler.blade.php`)
- SEBELUM: sidebar gelap 1 grup datar 19 item, topbar hanya avatar.
- SESUDAH: sidebar TERANG (surface putih, teks navy, indikator aktif
  + grup 7 kategori), collapse persisten (localStorage), drawer mobile
  + overlay + Esc, topbar dengan breadcrumb, pencarian global nyata
  (`/cari`), badge periode aktif, lonceng notifikasi + dropdown real,
  menu akun (Profil + Keluar). Footer konsisten.
- Bukti: `PencarianTest` (3 tes), composer notifikasi/periode.

## 3. Dashboard (`resources/views/dashboard.blade.php`, `/dashboard`)
- SEBELUM: 4 kartu + strip + 2 chart + 3 kolom + tabel.
- SESUDAH: hero sapaan (peran, tanggal, periode, CTA peran) → 6 metrik
  hero → strip identitas kampus → pertumbuhan per angkatan + donat
  status → arus kas + funnel PMB (BARU, data real) → performa + pengumuman
  → 3 antrean (agenda, KRS bimbingan, tunggakan scoped) → aktivitas +
  aksi cepat. Funnel PMB dihitung dari `pmb_pendaftars` riil.
- Bukti: `DashboardPerfTest` (ambang 100 query), `HardeningTest` scope.

## 4. Modul (`keuangan/bayar`, KRS pengajuan/persetujuan)
- Bayar: badge status, empty state, breadcrumb, tanpa `alert()` blokir
  (diganti live region + loading), tautan login→profil diperbaiki.
- KRS: header deskriptif + breadcrumb + konteks scope bimbingan.
- Tombol tanpa kelas Tabler: 0 tersisa di alur KRS (diaudit via grep).

## Cara verifikasi visual manual
1. `php artisan serve`, login tiap peran (lihat docs/USER_GUIDE.md).
2. Bandingkan dengan `git show HEAD~N:path` untuk versi lama
   (riwayat tersedia karena perubahan di-commit per ronde sebelumnya;
   ronde ini TIDAK di-commit sesuai arahan).
3. Checklist 10 pertanyaan penerimaan ada di `docs/UI_UX_SCORECARD.md`.
