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

- [x] **Ekspor rekap piutang aging.** SELESAI 2026-11-16: `KeuanganService::piutangAging` (bucket 0-30/31-60/61-90/>90 dari `hariTerlambat`, hanya sisa>0) + rute CSV `/keuangan/piutang-aging`. Tes: bucket benar + total bucket = total piutang.
- [x] **Pengingat denda otomatis.** SELESAI 2026-11-16: rincian denda di `pesanPengingat` (`termasuk denda RpX`). Tes: pesan memuat nominal.
- [x] **Kuitansi gabungan multi-tagihan.** SELESAI 2026-11-16: `KwitansiController::gabungan` + view `cetak.kwitansi-gabungan` (semua pembayaran verified 1 mhs + 1 periode, otorisasi owner-scope). Tes: 2 bayar → total 1.500.000 tampil.

## 6. GL & anggaran (`GlService`, `AnggaranService`, `TutupBuku`)

- [x] **Saldo awal periode.** SELESAI 2026-11-16: `GlService::postingSaldoAwal` (jurnal berimbang referensi SALDO-AWAL, idempoten per tahun) — mengalir otomatis ke neraca/buku besar/laba-rugi tanpa tabel khusus. Tes: sekali masuk neraca, ulangan ditolak.
- [x] **Laporan laba-rugi.** SELESAI 2026-11-16: `GlService::labaRugi` (pendapatan vs beban per akun tipe COA + surplus) + kartu Laba-Rugi di halaman GL. Tes: 1jt − 400rb = surplus 600rb.
- [x] **Arus kas sederhana.** SELESAI 2026-11-16: `HutangService::bayar` menjurnalkan kas keluar (`pengeluaran`; migrasi enum + rebuild SQLite) + `KeuanganService::arusKas` (masuk = penerimaan−refund, keluar, bersih, per bulan) + kartu di halaman Hutang. Temuan: rebuild tabel wajib salin `deleted_at` (SoftDeletes) + lepas semua index lama dulu (nama index global di SQLite). Tes: 2jt − 800rb = 1,2jt tampil.

## 7. Payroll & HR (`PayrollService`, `Kelola` rekrutmen/organisasi)

- [x] **Slip gaji cetak (bukan PDF).** SELESAI 2026-11-16: `SlipController::cetak` + view print-CSS + tombol Cetak per baris (tanpa dependensi PDF baru, pola transkrip). Tes: nama + total tampil.
- [x] **Tunjangan per peran (template).** SELESAI 2026-11-16: tabel `tunjangan_template` (role → nominal) + kelola di halaman payroll + isi otomatis saat pegawai dipilih (bisa diubah manual). Catatan: per-peran, bukan jabatan struktural (master jabatan belum ada). Tes: pilih dosen → 750rb + simpan template tendik.
- [x] **Onboarding checklist.** SELESAI 2026-11-16: tabel `onboarding_checklist` + 3 item bawaan otomatis saat lamaran diterima (idempoten) + centang di tabel pelamar. Tes: 3 item muncul + toggle selesai.

## 8. BKD & SK (`BkdService`, `Bkd/Kelola`)

- [x] **Cetak SK mengajar.** SELESAI 2026-11-16: `SkMengajarController::cetak` (generateSk idempoten + template + QR bertanda + stamp, print-CSS tanpa PDF baru) + verifikasi publik + otorisasi owner (dosen sendiri / admin). Tes: cetak OK + verify VALID + tanda palsu 403.
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
- [x] **Pengingat tracer via email.** SELESAI 2026-11-16: command `tracer:ingatkan` (target = lulus ~6 bulan tanpa isian; proxy tanggal lulus = updated_at — kolom khusus belum ada) + `TracerUndanganMail`; guard `MailService::terkonfigurasi` → dry-run daftar target. Tes: dry-run memuat NIM target.

## 12. Organisasi & kemahasiswaan (`Organisasi/Kelola`, `Cuti`)

- [x] **SK kepengurusan.** SELESAI 2026-11-16: kolom `nomor_sk` + input manual (otomatis `SK-ORG/tahun/id` bila kosong) + kolom di daftar. Tes: simpan tanpa nomor → nomor terisi + tampil.
- [x] **Cuti: lampiran + histori.** SELESAI 2026-11-16: kolom `lampiran_path` (migrasi) + unggah opsional PDF/gambar ≤10MB saat ajukan + kolom Lampiran (tautan Lihat) di riwayat; histori daftar sudah ada. Tes: file tersimpan di disk + tautan tampil.

## 13. Aset & fasilitas (`AsetService`, `PeminjamanRuang`)

- [x] **Opname aset.** SELESAI 2026-11-16: tabel `aset_opname` + `Aset/Opname` (catat fisik vs buku, terapkan kondisi ke aset, badge cocok/selisih + audit) di `/aset/opname`. Batas jujur: jurnal penyesuaian GL belum otomatis (butuh pemetaan COA). Tes: fisik hilang → aset hilang + selisih tercatat.
- [x] **Kalender pemakaian ruang.** SELESAI 2026-11-16: `Jadwal/KalenderRuang` (mingguan, kuliah + booking, geser pekan, filter ruang) di `/fasilitas/kalender`. Temuan: `whereIn(tanggal)` gagal di SQLite, wajib `whereDate` (pola sama seperti `ai_kuota`).

## 14. Perpustakaan (modul baru — desain dulu, vertikal slice)

- [x] **Sirkulasi inti.** SELESAI 2026-11-16 (`perpus_*` + `PerpustakaanService` + UI sirkulasi + peran pustakawan + `PerpusTest`). Proposal batch 1: `buku` + `eksemplar` +
      pinjam/kembali + denda (pola denda tagihan) + batas pinjam.
      (L; terima: pinjam→kembali→denda terhitung.)
- [x] **OPAC publik.** SELESAI 2026-11-16: `Perpus/Katalog` di `/katalog` (pencarian + badge ketersediaan).
      (M, setelah sirkulasi.)

## 15. Asrama (modul baru)

- [x] **Alokasi kamar.** SELESAI 2026-11-16: `Asrama/Kelola` — kamar (gedung/kode/kapasitas/iuran) + hunikan by NIM + keluarkan + audit; 1 bed 1 penghuni (kapasitas), 1 mhs 1 kamar (unique). Tes: ganda + penuh ditolak.
- [x] **Iuran asrama.** SELESAI 2026-11-16: jenis tagihan `asrama` (enum MySQL + rebuild SQLite, ImporCsv ikut) + `tarikIuran` (firstOrCreate per penghuni aktif, periode berjalan) — flow bayar existing langsung bisa dipakai. Tes: 1 tagihan 750rb + idempoten.

## 16. Klinik (modul baru — data sensitif!)

- [x] **Kunjungan + resep.** SELESAI 2026-11-16: modul klinik batch 1 — `klinik_kunjungan` + `klinik_resep`, peran `petugas_klinik` (permission `klinik.manage/read`), gate ganda (middleware + `can()` di tiap aksi; rute di luar grup admin), audit tulis + audit baca per rekam. Tes: non-klinik 403 dua lapis, simpan + resep + audit tercatat.

## 17. SPMI & akreditasi lanjutan (`Admin/Akreditasi`, `Obe/*`)

- [x] **Siklus PPEMPMTI.** SELESAI 2026-11-16: tabel `spmi_siklus` (unik prodi+tahun+tahap, 5 tahap + status) + kartu di halaman Akreditasi (idempoten via updateOrCreate). Tes: simpan → tampil; simpan ulang tak ganda.
- [x] **Sub-CPMK.** SELESAI 2026-11-16: tabel `sub_cpmk` (unik per CPMK) + `simpanSubCpmk` + kartu form + kolom badge di daftar OBE. Tes: SUB-1 tersimpan + tampil.
- [x] **Borang otomatis.** SELESAI 2026-11-16: `BorangService::ringkasan` (profil mhs + cakupan CPMK/asesmen + tren tracer + dokumen per kategori) + rute CSV `/admin/akreditasi/borang/{prodi}` + tombol per prodi. Batas jujur: draf ekspor, BUKAN klaim akreditasi. Tes: angka ringkasan + header unduhan.

## 18. AI (`ChatbotService`, `RagService`, `AiKuotaService`, `AtRiskService`)

- [x] **Sumber RAG per prodi.** SELESAI 2026-11-16: `knowledge_base.prodi_id` (null = global) + filter di `RagGuardrail::relevan` + `ingest/jawab` ber-parameter + API meneruskan prodi penanya (mahasiswa/dosen). Kompatibel: pemanggil tanpa prodi tak berubah. Tes: dokumen prodi A tak bocor ke prodi B.
- [x] **Evaluasi RAG.** SELESAI 2026-11-16: `RagService::evaluasi` (F1 kata vs jawaban emas, deterministik tanpa panggil AI) + command `rag:evaluasi --file=emas.json [--prodi=]`. Tes: relevan > tak-relevan + command mencetak rata-rata.
- [x] **At-risk: notifikasi wali.** SELESAI 2026-11-16: tombol "Kirim ke PA" per baris level tinggi di `Analitik/AtRisk` → notifikasi inbox ke Dosen PA (try/catch, tanpa gagalkan halaman). Perbaikan penghalang: `daftarBerisiko` memilih kolom `nama` yang tak ada di tabel mahasiswa + query `is_late` atas kolom yang belum ada → daftar selalu kosong; diperbaiki via nama dari relasi `user` + migrasi `add_is_late_to_tugas_kumpul_table`. Tes: 61 skor → notif PA tercatat.
- [x] **Batas konteks materi RAG.** SELESAI 2026-11-16: `RagService::MAX_KONTEKS` (4000 karakter, marker potong); sumber lengkap tetap dikembalikan. Tes: dokumen ~20rb karakter tetap terjawab + bersumber.

## 19. API & mobile (`api.php`, `mobile/`, OpenAPI)

- [x] **Filter/sort/paging konsisten.** SELESAI 2026-11-16: helper `ApiPaging` (`?q=&sort=&per_page=` + envelope `{data, meta}`) dipakai tugas + tagihan + pengumuman. Catatan kontrak: tugas yang dulu `limit(20)` kini paginasi; ringkasan tagihan dari full filter; meta pengumuman + `per_page`. Tes: `Batch21Test` (3 kontrak).
- [x] **API orangtua (read-only).** SELESAI 2026-11-16: pivot `wali_mahasiswa` + `WaliController` (daftar anak, nilai terkunci, tagihan) + command `wali:tautkan`; 403 tanpa tautan/anak orang lain. Tes: siklus taut → baca → tolak.
- [ ] **Push topik per prodi.** Token ada; segmentasi belum dipakai.
      Proposal: kirim pengumuman ke topik prodi saat publish. (M;
      butuh kredensial FCM — kaitkan M-064.)
      ⏸ DITUNDA 2026-11-16: butuh kredensial FCM server (eksternal);
      implementasi tanpa kredensial tak bisa diverifikasi.

## 20. PWA & offline (`sw.js`, `manifest`, `ScormService`)

- [x] **Halaman KHS offline.** SELESAI 2026-11-16: snapshot ringkasan (IPK/SKS/waktu) ke localStorage tiap kunjungan + banner luring (SW sudah cache shell). Tes: halaman menanam kunci cache + banner.
- [x] **Unduhan massal materi.** SELESAI 2026-11-16: `ScormService::paketKelas` (ZIP per kelas: folder per pertemuan + manifest gabungan + metadata) + rute `/lms/paket-kelas/{kelas}` (throttle 10/menit). Tes: ZIP memuat 2 materi + metadata benar.

## 21. Observability & operasi (`/health`, scheduler, backup)

- [x] **Cek antrean di /health.** SELESAI 2026-11-16: `queue` + `queue_failed` + `checks.queue` di respons `/health` (tabel `jobs`/`failed_jobs` diguard `Schema::hasTable`; metrik tak boleh gagalkan health). Tes: struktur JSON + `queue_failed: 0`.
- [x] **Uji restore terjadwal.** SELESAI 2026-11-16 (dokumen, tanpa kode): `docs/RUNBOOK_RESTORE.md` — prasyarat, langkah restore, verifikasi, checklist bukti, jadwal triwulan. Eksekusi manual pertama belum dilakukan (butuh staging).
- [x] **Log lambat.** SELESAI 2026-11-16: `DB::listen` di `AppServiceProvider` (> `config(app.slow_query_ms, 500)` → `Log::warning` + sql/bindings; ambang 0 = nonaktif; try/catch anti-ganggu). Tes: ambang raksasa → tidak ada warning (jalur positif tak deterministik di sqlite, dicatat jujur).

## 22. Keamanan (lanjutan batch RBAC)

- [x] **Audit baca data sensitif.** SELESAI 2026-11-15: tabel `audit_baca` + `AuditBacaService` (no-op aman, retensi 365 hari) + hook KHS/transkrip. Tes: `AuditBacaTest`.
      belum. Proposal: log baca dokumen mahasiswa (siapa,kapan).
      (M; tabel `audit_baca`, retensi 1 tahun.)
- [x] **Kunci akun brute-force.** SELESAI 2026-11-15: kunci 15 menit setelah 5 gagal per email (`SessionAuthController`, RateLimiter 900 dtk). Login throttle ada; lockout belum.
      Proposal: kunci 15 menit setelah 5 gagal + notifikasi email.
      (M.)
- [ ] **Rotasi token mobile.** Token abadi. Proposal: expiry + refresh
      endpoint (lihat kontrak Flutter dulu). (M.)
      ⏸ DITUNDA 2026-11-16: menyentuh kontrak auth aplikasi mobile;
      butuh keputusan expiry/refresh dari tim Flutter sebelum kode.
- [x] **Review permission tahunan.** SELESAI 2026-11-16 (verifikasi, tanpa kode baru): `permission:review` sudah mencakup peran-tanpa-user + permission-tanpa-peran + delegasi kedaluwarsa; tercakup `RbacBatch4Test`, berjalan bersih di dev. Dikunci `Batch8Test`.

## 23. UI/UX (tanpa E2E — tetap jujur)

- [x] **Empty-state audit (halaman batch 6–23).** SELESAI 2026-11-16: pesan informatif di tabel/daftar kosong (opname, klinik, asrama×2, kalender, rekrutmen×2, organisasi, labarugi). Batas jujur: halaman lama di luar cakupan batch ini. Tes: tiap pesan tampil.
- [x] **Aksesibilitas keyboard (fondasi + label).** SELESAI 2026-11-16: verifikasi skip-link + `:focus-visible` sudah ada di layout; checkbox onboarding dikaitkan label (`for`/`id`); input ambang ranking ber-`aria-label`. Uji tab-only manual penuh belum dilakukan (dicatat). Tes: asosiasi label ada.
- [x] **Mode cetak rapor.** SELESAI 2026-11-16: `public/css/cetak.css` (`@media print`: sembunyikan layout, tabel anti-potong + header berulang, badge hemat tinta) dimuat layout Tabler (`media=print`) sehingga berlaku di KHS/transkrip/slip/kwitansi/SK. Tes: link tampil + aturan kunci ada.

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
