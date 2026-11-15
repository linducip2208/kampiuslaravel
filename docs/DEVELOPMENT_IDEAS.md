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

- [ ] **Masa studi & tanggal lulus di KHS/transkrip.** Sekarang predikat IPK
      ada, tapi belum ada lama-studi/semester-ke. Proposal: hitung dari
      `angkatan` × periode aktif; tampilkan di `transkrip.cetak`.
      (S, tanpa dependensi; terima: tampil benar untuk angkatan 2020–2026.)
- [ ] **Arsip KRS per semester (PDF).** KRS detail sudah ada; belum ada
      unduhan arsip per periode. Proposal: tombol unduh di riwayat KRS
      (mirip `KwitansiController`). (S; terima: PDF berisi detail + QR.)
- [ ] **Peringatan SKS di pengajuan (bukan cuma blokir).** `validasiKrs`
      sudah blokir; tambahkan pesan dini "sisa SKS X" di UI pengajuan.
      (S; terima: teks muncul sebelum submit.)

## 2. PMB (`PmbController`, `PmbVaService`, `PmbFunnelController`)

- [ ] **Kedaluwarsa VA otomatis.** VA simulasi/prod tanpa expiry.
      Proposal: kolom `va_expires_at` + cron menandai kedaluwarsa +
      tombol "buat ulang VA". (M; terima: VA lama ditolak callback.)
- [ ] **Skoring seleksi otomatis.** Nilai seleksi ada
      (`pmb_nilai_seleksis`), belum ada perangkingan. Proposal: ranking
      per gelombang + ambang lulus di halaman verifikasi. (M; terima:
      tombol "proses perangkingan" mengisi status lulus/cadangan.)
- [ ] **Arsip berkas pendaftar.** Berkas di `storage/app/public/pmb/*`
      tanpa retensi. Proposal: tombol arsip per gelombang (ZIP) +
      hapus aman setelah N tahun. (M; terima: ZIP terunduh + audit.)

## 3. KRS & persetujuan (`Pengajuan`, `PersetujuanPa`, `WaliList`)

- [ ] **Riwayat revisi KRS.** Revisi menimpa tanpa jejak. Proposal:
      snapshot `krs_detail` tiap submit ulang + tab riwayat.
      (M; terima: 2x submit → 2 snapshot.)
- [x] **Notifikasi PA otomatis.** SELESAI 2026-11-15: notifikasi inbox ke Dosen PA tiap pengajuan lolos (`Pengajuan::ajukan`). Tes: 1 pengajuan = 1 notif. Persetujuan menunggu tanpa notif.
      Proposal: notifikasi inbox + (bila dikonfigurasi) WA/email ke PA
      saat ada pengajuan baru. (S; terima: 1 pengajuan = 1 notif PA.)

## 4. LMS & CBT (`MateriCrud`, `Tugas*`, `Kuis*`, `Forum`)

- [x] **Bank soal: impor CSV.** SELESAI 2026-11-15: `Lms/BankSoalImpor` di `/lms/bank-soal-impor` (`pertanyaan,jenis,opsi|,kunci,bobot`, delimiter otomatis, validasi per baris + laporan gagal). Tes: `BankSoalImporTest`.
      belum. Proposal: impor CSV (pertanyaan,opsi,kunci,bobot) dengan
      validasi baris + laporan gagal. (M; terima: 100 baris valid masuk.)
- [ ] **Bank soal: pakai ulang lintas kelas.** Soal terikat kuis,
      belum bisa klon antar-kelas. Proposal: tombol "klon ke kuis lain".
      (S; terima: soal tercopy, kunci ikut.)
- [x] **Forum: kutipan balasan.** SELESAI 2026-11-15: tombol Kutip mengisi draf (`Forum::kutip`).
      Proposal: tombol kutip mengisi balasan. (S.)
- [ ] **Materi: pratinjau PDF sebaris.** Sekarang unduhan saja. Proposal:
      embed viewer PDF di halaman materi. (S; terima: PDF tampil tanpa unduh.)

## 5. Keuangan (`KeuanganService`, `Bayar*`, `TagihanMassal`)

- [ ] **Ekspor rekap piutang aging.** Piutang ada, aging bucket belum.
      Proposal: kolom 0-30/31-60/61-90/>90 hari di ekspor tagihan.
      (M; terima: total per bucket = total piutang.)
- [ ] **Pengingat denda otomatis.** Denda dihitung (`denda:terapkan`),
      tapi pengingat manual. Proposal: sertakan denda berjalan di pesan
      `tagihan:ingatkan`. (S; terima: pesan memuat nominal denda.)
- [ ] **Kuitansi gabungan multi-tagihan.** Kwitansi per pembayaran saja.
      Proposal: cetak gabungan per mahasiswa per periode. (M.)

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
- [ ] **Rekap beban fakultas.** Rekap per dosen ada; agregat prodi/
      fakultas belum. Proposal: tab rekap per prodi. (S.)

## 9. Skripsi & sidang (`Yudisium`, `NilaiSidang`, `Pendaftaran`)

- [x] **Jadwal sidang bentrok ruang.** SELESAI 2026-11-15: cek `JadwalService::ruangBentrok` (±2 jam kuliah) saat simpan pendaftaran.
      bentrok kuliah/pinjam. Proposal: pakai `JadwalService::ruangBentrok`
      saat simpan jadwal sidang. (S; terima: bentrok ditolak.)
- [ ] **Penguji eksternal.** Penguji harus dosen internal. Proposal:
      izinkan penguji non-dosen (nama bebas) di `sidang_penguji`.
      (M; terima: nilai penguji eksternal masuk rata-rata.)

## 10. Yudisium, wisuda, alumni (`Yudisium`, batch, `Alumni`)

- [x] **Syarat wisuda otomatis (pratinjau).** SELESAI 2026-11-15: `WisudaService::cekSyarat` + tombol "Cek Syarat" di Yudisium. Alur `luluskan` TIDAK diubah; SKS = info acuan.
      (SKS lulus, IPK ≥ 2.0, bebas tagihan, judul lulus) sebelum daftar.
      (M; terima: tidak lolos syarat → tombol terkunci + alasan.)
- [ ] **Buku wisuda (PDF).** Batch ada; buku acara belum. Proposal:
      ekspor daftar wisudawan + NIM + predikat. (S.)

## 11. Tracer & karier (`Tracer/*`, `Admin/Tracer`)

- [ ] **Grafik serapan di dashboard.** Angka ada di eksekutif; tren
      tahunan belum. Proposal: grafik lulusan vs bekerja per tahun lulus.
      (S.)
- [ ] **Pengingat tracer via email.** Hanya form pasif. Proposal: kirim
      undangan tracer ke email alumni 6 bulan pasca-lulus (ikut pola
      `MailService`, guard konfigurasi). (M.)

## 12. Organisasi & kemahasiswaan (`Organisasi/Kelola`, `Cuti`)

- [ ] **SK kepengurusan.** Organisasi tanpa dokumen. Proposal: nomor SK
      + cetak (pola Surat). (M.)
- [ ] **Cuti: lampiran + histori.** Pengajuan teks saja. Proposal: kolom
      lampiran + linimasa status. (S.)

## 13. Aset & fasilitas (`AsetService`, `PeminjamanRuang`)

- [ ] **Opname aset.** Tanpa stok fisik vs buku. Proposal: sesi opname
      (cocok/hilang/rusak) + selisih ke jurnal penyesuaian. (M.)
- [ ] **Kalender pemakaian ruang.** Daftar doang. Proposal: tampilan
      kalender mingguan per ruang. (M; bisa pakai ulang pola KHS grid.)

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
- [ ] **At-risk: notifikasi wali.** Prediksi ada; tindak lanjut manual.
      Proposal: notifikasi otomatis ke dosen PA bila risiko tinggi.
      (S; guard kuota AI.)
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
- [ ] **Unduhan massal materi.** ZIP per pertemuan ada; per MK belum.
      Proposal: paket per matakuliah (semua pertemuan). (S.)

## 21. Observability & operasi (`/health`, scheduler, backup)

- [ ] **Cek antrean di /health.** Health cek DB+migrasi; queue gagal
      tak terpantau. Proposal: tambah `queue:failed` count + ukuran
      antrean. (S.)
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
- [ ] **Review permission tahunan.** Proposal: command daftar permission
      tak terpakai vs gate + laporan. (S.)

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
