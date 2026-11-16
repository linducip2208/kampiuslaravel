# Bank Ide Pengembangan per Fitur

> Sumber kebenaran status: `docs/MEGA_BACKLOG.md` (60/66 selesai).
> Dokumen ini = antrean ide **berikutnya**, boleh dikerjakan tanpa
> menunggu item BLOCKED. Setiap ide: masalah → proposal konkret →
> estimasi (S <1 hari, M 1–3 hari, L 1–2 minggu, XL >2 minggu) →
> dependensi → kriteria terima.
>
> Aturan main: 1 ide = 1 vertical slice (migrasi + kode + tes hijau).
> Dilarang: klaim tanpa kode, angka tanpa sumber, integrasi tanpa kredensial.

## Cara pakai

Pilih ide → baca file acuan → implementasi → tambah tes →
`php artisan test` hijau → `vendor/bin/pint --dirty` → commit "Batch: ..." →
update doc ini (`[x]`) + `MEGA_IMPLEMENTATION_LOG.md`.

---

## 1. Akademik inti (`AkademikService`, `KhsController`, `TranskripController`)

- [x] **Masa studi di transkrip.** SELESAI 2026-11-16: `AkademikService::masaStudi` (angkatan × periode aktif → semester-ke + label tahun/semester) + badge di `transkrip.cetak`. Catatan jujur: tanggal lulus belum ada (butuh kolom baru, di luar cakupan S). Tes: 2024 × 2027/ganjil = Semester 7 + tampil di halaman.
- [x] **Arsip KRS.** SELESAI 2026-11-16: riwayat 10 pengajuan terakhir + tautan unduh `krs.cetak` di halaman pengajuan (tanpa PDF baru, cetak existing dipakai ulang).
- [x] **Peringatan SKS di pengajuan.** SELESAI 2026-11-16: kolom "Sisa SKS" (`getSisaSksProperty = sksMaks - totalSks`) di kartu ringkasan pengajuan. Tes: teks tampil sebelum submit.

## 2. PMB (`PmbController`, `PmbVaService`, `PmbFunnelController`)

- [x] **Kedaluwarsa VA otomatis.** SELESAI 2026-11-16: `va_expires_at` (+7 hari, config `payment.defaults.va_expiry_days`) — VA lama dipakai ulang hanya bila berlaku, kedaluwarsa → terbit ulang; `settleByReference` menolak lewat tempo. Tes: reuse → regenerate → settle lama false → settle baru lunas.
- [x] **Skoring seleksi otomatis.** SELESAI 2026-11-16: status baru `cadangan` + `Verifikasi::prosesRanking` (urut nilaiAkhir, dalam kuota + ambang → lulus, luar kuota → cadangan, di bawah ambang → tidak; hanya status belum final) + filter gelombang + ambang + tombol Ranking. Tes: kuota 1 → 90 lulus, 80 cadangan.
- [x] **Arsip berkas pendaftar.** SELESAI 2026-11-16: `PmbArsipService::arsipGelombang` (ZIP per gelombang + manifest ada/hilang + AuditLog `pmb.arsip`) + rute `/admin/pmb/arsip/{gelombang}` (throttle 5/menit). Retensi/hapus fisik tetap manual. Tes: ZIP + manifest + audit + unduhan.

## 3. KRS & persetujuan (`Pengajuan`, `PersetujuanPa`, `WaliList`)

- [x] **Riwayat revisi KRS.** SELESAI 2026-11-16: tabel `krs_revisi` + snapshot state lama (state dibaca SEBELUM `updateOrCreate` menimpa) tiap submit ulang + tab "Riwayat Revisi". Tes: 2x submit → 1 snapshot berisi kelas lama.
- [x] **Notifikasi PA otomatis.** SELESAI 2026-11-15: notifikasi inbox ke Dosen PA tiap pengajuan lolos (`Pengajuan::ajukan`). Tes: 1 pengajuan = 1 notif. Persetujuan menunggu tanpa notif.
      Proposal: notifikasi inbox + (bila dikonfigurasi) WA/email ke PA
      saat ada pengajuan baru. (S; terima: 1 pengajuan = 1 notif PA.)

## 4. LMS & CBT (`MateriCrud`, `Tugas*`, `Kuis*`, `Forum`)

- [x] **Bank soal: impor CSV.** SELESAI 2026-11-15: `Lms/BankSoalImpor` di `/lms/bank-soal-impor` (`pertanyaan,jenis,opsi|,kunci,bobot`, delimiter otomatis, validasi per baris + laporan gagal). Tes: `BankSoalImporTest`.
      belum. Proposal: impor CSV (pertanyaan,opsi,kunci,bobot) dengan
      validasi baris + laporan gagal. (M; terima: 100 baris valid masuk.)
- [x] **Bank soal: pakai ulang lintas kelas.** SELESAI 2026-11-16: `BankSoal::klon` (salin isi + opsi + kunci + bobot ke MK tujuan via dropdown "Klon ke MK"). Tes: klon → 2 baris, kunci ikut.
- [x] **Forum: kutipan balasan.** SELESAI 2026-11-15: tombol Kutip mengisi draf (`Forum::kutip`).
      Proposal: tombol kutip mengisi balasan. (S.)
- [x] **Materi: pratinjau PDF sebaris.** SELESAI 2026-11-16: tombol Pratinjau/Tutup + `<embed>` PDF + tautan "Buka file" di daftar materi (non-PDF hanya tautan). Tes: `<embed>` + nama file tampil.

## 5. Keuangan (`KeuanganService`, `Bayar*`, `TagihanMassal`)

- [ ] **Ekspor rekap piutang aging.** Piutang ada, aging bucket belum.
      Proposal: kolom 0-30/31-60/61-90/>90 hari di ekspor tagihan.
      (M; terima: total per bucket = total piutang.)
- [x] **Pengingat denda otomatis.** SELESAI 2026-11-16: rincian denda di `pesanPengingat` (`termasuk denda RpX`). Tes: pesan memuat nominal.
- [x] **Kuitansi gabungan multi-tagihan.** SELESAI 2026-11-16: `KwitansiController::gabungan` + view `cetak.kwitansi-gabungan` (semua pembayaran verified 1 mhs + 1 periode, otorisasi owner-scope). Tes: 2 bayar → total 1.500.000 tampil.

## 6. GL & anggaran (`GlService`, `AnggaranService`, `TutupBuku`)

- [ ] **Saldo awal periode.** Neraca tanpa opening balance. Proposal:
      impor saldo awal per CoA per tahun + tampil di buku besar.
      (M; terima: saldo awal + mutasi = saldo akhir.)
- [ ] **Laporan laba-rugi.** Buku besar + neraca ada; L/R belum.
      Proposal: agregat pendapatan−beban per tipe CoA. (M.)
- [ ] **Arus kas sederhana.** Belum ada. Proposal: kas masuk (penerimaan)
      vs keluar (hutang lunas + payroll) per bulan. (M; terima: angka
      cocok dengan jurnal.)

## 7. Payroll & HR (`PayrollService`, `Kelola` rekrutmen/organisasi)

- [ ] **Slip PDF.** Slip hanya di layar. Proposal: cetak slip PDF per
      pegawai (mirip kwitansi). (S.)
- [ ] **Tunjangan per jabatan.** Komponen manual per slip. Proposal:
      template tunjangan per peran (dosen/tendik) sebagai default.
      (M; terima: slip baru terisi default template.)
- [ ] **Onboarding checklist.** Rekrutmen berhenti di "diterima".
      Proposal: checklist onboarding (akun, SK, orientasi) per lamaran
      diterima. (M.)

## 8. BKD & SK (`BkdService`, `Bkd/Kelola`)

- [ ] **Cetak SK mengajar.** Nomor SK ada, dokumen SK belum. Proposal:
      template SK + QR verifikasi (pola Surat). (M.)
- [x] **Rekap beban fakultas.** SELESAI 2026-11-16: `BkdService::rekapPerProdi` (SKS disetujui + jumlah dosen per prodi, hanya admin) di halaman BKD. Tes: agregat 6 SKS / 1 dosen.

## 9. Skripsi & sidang (`Yudisium`, `NilaiSidang`, `Pendaftaran`)

- [x] **Jadwal sidang bentrok ruang.** SELESAI 2026-11-15: cek `JadwalService::ruangBentrok` (±2 jam kuliah) saat simpan pendaftaran.
      bentrok kuliah/pinjam. Proposal: pakai `JadwalService::ruangBentrok`
      saat simpan jadwal sidang. (S; terima: bentrok ditolak.)
- [x] **Penguji eksternal.** SELESAI 2026-11-16: `sidang_penguji.nama_eksternal` + `dosen_id` nullable (rebuild tabel utk SQLite, MODIFY utk MySQL) + input nama di `NilaiSidang` (wajib salah satu) + nilai eksternal masuk rata-rata. Cakupan jujur: input mandiri (`NilaiPenguji`) tetap khusus dosen berakun. Tes: nilai 80 eksternal → rata sidang 80.

## 10. Yudisium, wisuda, alumni (`Yudisium`, batch, `Alumni`)

- [x] **Syarat wisuda otomatis (pratinjau).** SELESAI 2026-11-15: `WisudaService::cekSyarat` + tombol "Cek Syarat" di Yudisium. Alur `luluskan` TIDAK diubah; SKS = info acuan.
      (SKS lulus, IPK ≥ 2.0, bebas tagihan, judul lulus) sebelum daftar.
      (M; terima: tidak lolos syarat → tombol terkunci + alasan.)
- [x] **Buku wisuda (CSV, bukan PDF).** SELESAI 2026-11-16: ekspor CSV per batch via `Yudisium::eksporCsv` (tanpa dependensi PDF baru). Tes: file terunduh.

## 11. Tracer & karier (`Tracer/*`, `Admin/Tracer`)

- [x] **Grafik serapan di dashboard.** SELESAI 2026-11-16: tabel tren di `Admin/Tracer` (responden vs terserap bekerja/wirausaha + bar % per `tahun_lulus`). Tes: 2024 = 2 responden/1 terserap/50%.
- [ ] **Pengingat tracer via email.** Hanya form pasif. Proposal: kirim
      undangan tracer ke email alumni 6 bulan pasca-lulus (ikut pola
      `MailService`, guard konfigurasi). (M.)

## 12. Organisasi & kemahasiswaan (`Organisasi/Kelola`, `Cuti`)

- [x] **SK kepengurusan.** SELESAI 2026-11-16: kolom `nomor_sk` + input manual (otomatis `SK-ORG/tahun/id` bila kosong) + kolom di daftar. Tes: simpan tanpa nomor → nomor terisi + tampil.
- [x] **Cuti: lampiran + histori.** SELESAI 2026-11-16: kolom `lampiran_path` (migrasi) + unggah opsional PDF/gambar ≤10MB saat ajukan + kolom Lampiran (tautan Lihat) di riwayat; histori daftar sudah ada. Tes: file tersimpan di disk + tautan tampil.

## 13. Aset & fasilitas (`AsetService`, `PeminjamanRuang`)

- [ ] **Opname aset.** Tanpa stok fisik vs buku. Proposal: sesi opname
      (cocok/hilang/rusak) + selisih ke jurnal penyesuaian. (M.)
- [x] **Kalender pemakaian ruang.** SELESAI 2026-11-16: `Jadwal/KalenderRuang` (mingguan, kuliah + booking, geser pekan, filter ruang) di `/fasilitas/kalender`. Temuan: `whereIn(tanggal)` gagal di SQLite, wajib `whereDate` (pola sama seperti `ai_kuota`).

## 14. Perpustakaan (modul baru — desain dulu, vertikal slice)

- [x] **Sirkulasi inti.** SELESAI 2026-11-16 (`perpus_*` + `PerpustakaanService` + UI sirkulasi + peran pustakawan + `PerpusTest`). Proposal batch 1: `buku` + `eksemplar` +
      pinjam/kembali + denda (pola denda tagihan) + batas pinjam.
      (L; terima: pinjam→kembali→denda terhitung.)
- [x] **OPAC publik.** SELESAI 2026-11-16: `Perpus/Katalog` di `/katalog` (pencarian + badge ketersediaan).
      (M, setelah sirkulasi.)

## 15. Asrama (modul baru)

- [ ] **Alokasi kamar.** Proposal: gedung→kamar→bed, isi dari mahasiswa
      aktif, cek ganda 1 bed 1 penghuni. (L.)
- [ ] **Iuran asrama.** Proposal: tagihan jenis baru + flow bayar existing.
      (M, setelah alokasi.)

## 16. Klinik (modul baru — data sensitif!)

- [ ] **Kunjungan + resep.** Wajib: gate `klinik.*` terpisah, audit akses
      per-rekam-medis, BUKAN di grup umum. Proposal batch 1: antrean +
      rekam sederhana + audit baca. (L; terima: non-klinik 403 + audit
      tiap akses.)

## 17. SPMI & akreditasi lanjutan (`Admin/Akreditasi`, `Obe/*`)

- [ ] **Siklus PPEMPMTI.** Dokumen ada; siklus belum. Proposal: tabel
      siklus (penetapan→pelaksanaan→evaluasi→pengendalian→peningkatan)
      + status per standar. (L.)
- [ ] **Sub-CPMK.** CPMK ada; breakdown belum. Proposal: `sub_cpmk`
      di bawah CPMK + bobot waris di ketercapaian. (M.)
- [ ] **Borang otomatis.** Proposal: isi borang dari rekap OBE + tracer
      (ekspor, bukan klaim akreditasi). (L.)

## 18. AI (`ChatbotService`, `RagService`, `AiKuotaService`, `AtRiskService`)

- [ ] **Sumber RAG per prodi.** RAG global; belum ada filter prodi.
      Proposal: kolom `prodi_id` knowledge + filter saat retrieval.
      (S.)
- [ ] **Evaluasi RAG.** Tanpa metrik. Proposal: set Q/A emas + skor
      kecocokan berkala (command). (M.)
- [x] **At-risk: notifikasi wali.** SELESAI 2026-11-16: tombol "Kirim ke PA" per baris level tinggi di `Analitik/AtRisk` → notifikasi inbox ke Dosen PA (try/catch, tanpa gagalkan halaman). Perbaikan penghalang: `daftarBerisiko` memilih kolom `nama` yang tak ada di tabel mahasiswa + query `is_late` atas kolom yang belum ada → daftar selalu kosong; diperbaiki via nama dari relasi `user` + migrasi `add_is_late_to_tugas_kumpul_table`. Tes: 61 skor → notif PA tercatat.
- [ ] **Batas konteks materi RAG.** Ingest teks panjang utuh. Proposal:
      potong per N kalimat + tautan sumber di jawaban. (S.)

## 19. API & mobile (`api.php`, `mobile/`, OpenAPI)

- [ ] **Filter/sort/paging konsisten.** v2 paginasi; v1 campur aduk.
      Proposal: samakan envelope + `?sort=&q=` di 3 endpoint inti
      (tugas, tagihan, pengumuman) + tes kontrak. (M.)
- [ ] **API orangtua (read-only).** Proposal: token wali + endpoint
      KHS/tagihan milik anak saja (IDOR ketat). (L.)
- [ ] **Push topik per prodi.** Token ada; segmentasi belum dipakai.
      Proposal: kirim pengumuman ke topik prodi saat publish. (M;
      butuh kredensial FCM — kaitkan M-064.)

## 20. PWA & offline (`sw.js`, `manifest`, `ScormService`)

- [ ] **Halaman KHS offline.** SW cache shell; data belum. Proposal:
      cache respons KHS terakhir + badge "data offline". (M.)
- [x] **Unduhan massal materi.** SELESAI 2026-11-16: `ScormService::paketKelas` (ZIP per kelas: folder per pertemuan + manifest gabungan + metadata) + rute `/lms/paket-kelas/{kelas}` (throttle 10/menit). Tes: ZIP memuat 2 materi + metadata benar.

## 21. Observability & operasi (`/health`, scheduler, backup)

- [x] **Cek antrean di /health.** SELESAI 2026-11-16: `queue` + `queue_failed` + `checks.queue` di respons `/health` (tabel `jobs`/`failed_jobs` diguard `Schema::hasTable`; metrik tak boleh gagalkan health). Tes: struktur JSON + `queue_failed: 0`.
- [ ] **Uji restore terjadwal.** Backup teruji; restore belum pernah
      dicoba di staging. Proposal: runbook + checklist + jadwal
      triwulan (manual, catat buktinya). (S, non-kode.)
- [ ] **Log lambat.** Tanpa pencatatan query lambat. Proposal: listener
      query >500ms → log + ringkasan harian di audit. (M.)

## 22. Keamanan (lanjutan batch RBAC)

- [x] **Audit baca data sensitif.** SELESAI 2026-11-15: tabel `audit_baca` + `AuditBacaService` (no-op aman, retensi 365 hari) + hook KHS/transkrip. Tes: `AuditBacaTest`.
      belum. Proposal: log baca dokumen mahasiswa (siapa,kapan).
      (M; tabel `audit_baca`, retensi 1 tahun.)
- [x] **Kunci akun brute-force.** SELESAI 2026-11-15: kunci 15 menit setelah 5 gagal per email (`SessionAuthController`, RateLimiter 900 dtk). Login throttle ada; lockout belum.
      Proposal: kunci 15 menit setelah 5 gagal + notifikasi email.
      (M.)
- [ ] **Rotasi token mobile.** Token abadi. Proposal: expiry + refresh
      endpoint (lihat kontrak Flutter dulu). (M.)
- [x] **Review permission tahunan.** SELESAI 2026-11-16 (verifikasi, tanpa kode baru): `permission:review` sudah mencakup peran-tanpa-user + permission-tanpa-peran + delegasi kedaluwarsa; tercakup `RbacBatch4Test`, berjalan bersih di dev. Dikunci `Batch8Test`.

## 23. UI/UX (tanpa E2E — tetap jujur)

- [ ] **Empty-state & error-state audit.** Telusuri tiap halaman:
      tanpa data, gagal validasi, 403/404/500 — harus informatif.
      (M; checklist manual + screenshot.)
- [ ] **Aksesibilitas keyboard.** Tab order + fokus terlihat + label
      form. (M; uji manual tab-only.)
- [ ] **Mode cetak rapor.** Beberapa cetak belum rapi di kertas.
      Proposal: stylesheet print per dokumen (KHS, transkrip, slip).
      (S.)

---

## Antrean yang DISARANKAN (nilai/usaha, tanpa BLOCKED)

1. Pengingat denda di tagihan (S) + audit baca dokumen (M) — keamanan kas.
2. Sirkulasi perpustakaan batch 1 (L) — modul baru paling diminta kampus.
3. Filter/sort paging API (M) — fondasi mobile.
4. Syarat wisuda otomatis (M) — menghilangkan cek manual.
5. KHS offline (M) — melengkapi PWA.
6. Jadwal sidang anti-bentrok (S) — bug laten nyata.

## TIDAK dikerjakan (jujur, bukan backlog)

- Multi-kampus/tenant: DITOLAK (arsitektur single-institution).
- Wave D integrasi: BLOCKED sampai kredensial nyata (lihat matriks §11 INTEGRASI.md).
- Klaim skala/E2E/audit eksternal: butuh lingkungan & pihak ketiga.
