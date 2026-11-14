# MEGA UI/UX REPORT — audit + arah redesign

## A. Yang sudah baik (bukti render via tes)

- Landing 11 seksi: hero, band foto kampus (WAN 2.1 lokal), value, mock produk,
  foto keuangan, lifecycle, peran + banner akademik, keamanan, FAQ, CTA wisuda,
  footer — semua `asset()` lokal, tanpa placeholder statistik (angka dari DB).
- Logo KAMPUS v2 (emblem WAN + wordmark PIL), favicon + apple-touch-icon.
- Shell Tabler terang, sidebar grup permission-aware, dashboard 8 blok,
  error pages 403/404/500 (ErrorPageTest), pencarian global (PencarianTest).
- Loading/empty/success states: mayoritas Livewire punya; belum diaudit satu per satu.

## B. Lubang (Wave B/C, diverifikasi agen)

1. Tanpa E2E/browser test dan tanpa screenshot terarsip — semua klaim visual
   hanya "render HTTP 200". Syarat naik skor UI: tambah tes browser rute kritis
   + arsipkan screenshot di `docs/screenshots/`.
2. Aksesibilitas keyboard & kontras belum diaudit (target: fokus terlihat,
   navigasi penuh tanpa mouse di alur KRS & bayar).
3. PWA: tidak ada manifest/service-worker (target C: portal mahasiswa offline
   baca jadwal/KHS).
4. CDN Tabler untuk CSS inti (Fase 4 minta aset lokal untuk core) — evaluasi:
   vendor-kan atau pertahankan dengan SRI + fallback.
5. Inkonsistensi kecil: kelas CSS `kampius-*` sisa rebrand (fungsional, tapi
   ganti ke `kampus-*` saat sentuh file terkait).
6. Mobile: tata letak responsif ada (Tabler), tapi alur KRS/bayar belum diuji
   sistematis di viewport kecil (masuk skenario browser test).

## C. Prinsip redesign (mengikat, bukan sekadar ganti warna)

- Setiap tabel: paginasi + filter + bulk action + empty-state beraksi.
- Setiap form: 1 kolom mobile, error inline per-field, konfirmasi aksi destruktif.
- Setiap dashboard: KPI dari query agregat (bukan angka statis), tautan drill-down.
- Aksi kritis (bayar, setujui KRS, kunci nilai, luluskan) selalu dalam jangkauan
  1 klik dari konteksnya + butuh konfirmasi + catat audit.
