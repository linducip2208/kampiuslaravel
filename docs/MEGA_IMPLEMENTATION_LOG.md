# MEGA IMPLEMENTATION LOG — catatan berjalan

## 2026-11-14 — FASE 0: audit forensik + baseline (4 agen paralel)

- Baseline aktual: 74 models / 53 migrasi / 44 Livewire / 32 controller /
  88 Blade / 44 file tes / 15 factory / 4 seeder / ±130 routes / 46 docs.
- Tes aktual: **212/212 hijau, 1223 asersi** (`php artisan test --compact`).
- 4 laporan agen: auth+otorisasi, keuangan, akademik, LMS+dokumen → disintesis ke
  MEGA_AUDIT_2026, SECURITY, PERFORMANCE, UI_UX, TEST, INVENTORY, BACKLOG (66 item),
  DEPENDENCIES, SCORECARD (57/100).
- Keputusan: target 1.500 item DITOLAK_WITH_EVIDENCE (lihat BACKLOG).
- Status git: bersih @ `10baa98`. TIDAK ADA kode diubah ronde ini (hanya docs,
  setelah ini). Tidak ada commit/push tanpa perintah eksplisit.

## 2026-11-14 — WAVE A batch 1: 13 item selesai, 223/223 hijau

Kode (pola laravel-best-practices: ikuti konvensi repo, tanpa pola baru):
- M-001 `BayarController::callback` — fallback PMBVA hanya driver simulasi
  (tolak signature-null di driver nyata); M-002 tx+`lockForUpdate` di
  `PmbVaService::settleByReference`; M-003 cek pending+lunas di dalam tx
  terkunci di `BayarManual::simpan`; M-004 check → driver → recheck-tx di
  `PaymentService::createTransaction` (tanpa menahan lock saat HTTP).
- M-007 IPS semester berjalan (`KhsController::ipSemesterBerjalan`, bukan IPK
  global); M-008 IPK server-side di `Pengajuan` (input browser diabaikan,
  field readonly + snapshot = server); M-009 IPK wali berbobot SKS
  (single-query; temuan turunan: integer-division SQLite → `* 1.0`).
- M-011 piutang/lunas_pct termasuk denda di 3 titik (dashboard, pimpinan,
  landing `web.php:80,115`); M-012 `postPenerimaan` tolak nominal ≤0 + assert
  seimbang; M-013 migrasi `2026_11_14_182700` index wave_a; M-014 migrasi
  `2026_11_14_182701` soft deletes `tagihan_cicilan` + `SoftDeletes` model;
  M-015 `TagihanPolicy` (`view`/`bayar`/`verify`) + `PembayaranPolicy`
  (`view`/`refund`), registrasi `Gate::policy`, `BayarController` delegasi
  via `AuthorizesRequests`; M-019 rename `ExampleUnitTest`.
- Tes: `tests/Feature/WaveACriticalTest.php` (11 tes: IPS, IPK-manual,
  IPK-wali, callback-PMBVA-403, settle ganda, bayar-manual ganda, piutang,
  jurnal-nol, cicilan restore, policy HTTP + matriks Gate).

## 2026-11-14 — WAVE A batch 2: 7 item terakhir selesai, WAVE A 20/20, 231/231 hijau

Kode:
- M-005 `PddiktiController::ALLOWED_ACTS` — `push()` validasi `in:` allowlist
  (GetToken, Insert/Update/Delete/Get Mhs+Nilai+Kelulusan); act liar → 422.
- M-006 `PddiktiController::export()` — tulis `AuditLog` (pddikti.export, milik
  user) tiap export sebelum `Storage::download`.
- M-010 `AkademikService::ringkasan($id, $hanyaTerkunci = true)` — default
  hanya `is_locked`; KHS/Transkrip/Yudisium/at-risk otomatis resmi; data lama
  WaveACriticalTest diberi `is_locked=true` (nilai draft tak masuk IPK resmi).
- M-016 grup audit viewer `role:...` → `permission:laporan.view` (pola Spatie;
  superadmin/admin_prodi/pimpinan semua pegang izin ini); `RouteGuardTest`
  diselaraskan ke ekspektasi permission.
- M-017 migrasi+model `webhook_events` (sumber/event/idempotency_key unik/
  payload); `PublikApiController::webhook` dedupe by signature-hash → replay
  200 `deduped:true` 1 baris; `BayarController::callback` hormati header
  `Idempotency-Key` (fallback hash konten+reference) + catat event payment.
- M-018 `Kelola::assign()` tegakkan `kuota` (hitung `mahasiswa_beasiswa`;
  milik-sendiri dikecualikan; habis → error validasi, tanpa baris baru).
- M-020 `WaveABatch2Test::test_upload_traversal_dinetralkan` — `.php` traversal
  ditolak 422 (mimes), PDF valid tersimpan hash tanpa `..`, route dokumentasi
  traversal tetap 404 (pola DokumenTraversalTest).
- Tes: `tests/Feature/WaveABatch2Test.php` (8 tes: allowlist, audit-export,
  locked-only, permission, webhook-replay, payment-idempotency, kuota, traversal).

## 2026-11-14 — WAVE B batch 1: 8 item P0/P1 selesai, 244/244 hijau

Kode:
- M-021 `TahunAkademik::aktifkan()` (tx + lockForUpdate, nonaktifkan lain dulu)
  + `::rollover()`/`::berikutnya()` idempoten (firstOrCreate) + `scopeAktif`;
  migrasi `2026_11_14_230815` (index `aktif`, reparasi >1 aktif → pertahankan
  terbaru; tanpa partial-unique — tidak portabel SQLite/MySQL, invarian di
  transaksi); command `akademik:rollover [--tahun --semester]`.
- M-024 `KhsController::show` + `TranskripController::cetak` terima `Request`:
  IPK global dari `ringkasan()`, baris display `Nilai` terkunci dipaginasi 25
  + filter `?tahun=` (tahun_akademik_id) + `tahunOptions`; Blade `khs/show`,
  `transkrip/cetak` tambah form filter + `links()` (d-print-none).
- M-027 tanpa ubah prod (validasi/otorisasi sudah benar) — bukti via
  `WaveBBatch1Test` (TugasCrud dosen-OK/mahasiswa-403, deadline, anti-spoof
  owner, mimes materi).
- M-037 `LaporanController implements HasMiddleware` → `can:laporan.view`
  (defense-in-depth; chunk(500)+streamDownload tidak berubah).
- M-045 `App\Support\ApiEnvelope::ok/fail` (kunci lama dipertahankan top-level);
  v2 `status/prodi/pengumuman/webhook` memakai envelope; baru
  `App\Http\Controllers\Api\OpenApiController` + `GET /api/openapi.json`
  (throttle 60,1, ter-generate dari rute `api/*`).
- M-046 throttle:30,1 di `pddikti.push`, `pddikti.export`, `keuangan.refund`
  (callback + sync sudah ada).
- M-047 `backup:create` (semua tabel → JSON-gzip chunked + manifest + latest)
  + `backup:verify` (gunzip + JSON valid + cocok hitung DB); `BACKUP_RESTORE.md`
  ditambah §Backup artisan.
- M-048 `/health` + `v2/status` tambah `checks`/`db`/`pending_migrations`
  (aditif; asersi lama tetap hijau).
- Tes: `tests/Feature/WaveBBatch1Test.php` (13 tes).

## 2026-11-14 — WAVE B batch 2: 6 item selesai, 252/252 hijau

Kode:
- M-022 `routes/console.php`: `akademik:rollover` terjadwal `yearlyOn(2,1)` +
  `yearlyOn(8,1)` 00:30 + `withoutOverlapping` (idempoten; nonaktifkan manual
  bila kalender menyimpang).
- M-023 `AkademikService::kandidatDo($minSemester = 4, $batasIpk = 2.0)` —
  1 query agregat (IPK terbobot + distinct semester, hanya status aktif);
  command `akademik:evaluasi-do [--min-semester] [--batas-ipk] [--terapkan]`
  (kering default; --terapkan ubah aktif→do + AuditLog, hanya status aktif).
- M-025/P1 `JadwalService::{ruangBentrok,dosenBentrok,pinjamRuang}` → predikat
  overlap di sisi DB + `normalisasiJam()` (batas tepat 09:30 vs 09:30:00 tidak
  salah overlap); `jamOverlap()` dipertahankan untuk BC.
- M-025/P3 `validasiKrs()`: prasyarat 1 query lulus (distinct matakuliah) +
  kuota via `kursiTerisiBanyak()` (2 query untuk N kelas, bukan 2N).
- M-026 `RekomendasiMkService::rekomendasi()` tambah `matakuliah_id` per ulangi
  + `kelas_tersedia[mk]` (periode aktif: kelas_id/kelas/terisi/kuota/penuh +
  `posisi_waitlist` bila penuh); tanpa periode aktif → peta kosong; BC
  (`ulangi`, `batas_sks`, `saran` tak berubah — `AiFeatureTest` hijau).
- M-035 tanpa ubah prod (service idempoten + UI + tes sudah ada) — bukti baru:
  `tak_dikenal` + re-run tak mengubah `reconciled_at`.
- M-041 `setujuiPinjam()` cek-ulang kuliah+booking di tx terkunci + tolak
  non-diajukan (422); rute `GET /fasilitas/pinjam` (semua login; setujui/tolak
  tetap superadmin/admin_prodi di komponen).
- Tes: `tests/Feature/WaveBBatch2Test.php` (8 tes).

## 2026-11-14 — WAVE B batch 3: 6 item selesai, 260/260 hijau

Kode (vertical slice: migrasi + model + Livewire + blade + rute + tes):
- M-032 migrasi `tutup_buku` (tahun_akademik_id unik, status, ditutup/dibuka
  oleh+at, catatan) + model `TutupBuku::terkunci()`; guard di
  `KeuanganService::postPenerimaan()` + `::refundPembayaran()` (throw bila
  terkunci); Livewire `Keuangan/TutupBuku` (tutup/buka + AuditLog) di grup
  keuangan (`/keuangan/tutup-buku`).
- M-033 migrasi `anggaran` (unik tahun+kode) + model + `AnggaranService`
  (`realisasi()` = jurnal penerimaan−refund per jenis+tahun;
  `ringkasanPeriode()`); Livewire `Keuangan/Anggaran` (CRUD + tabel
  realisasi/sisa/%) di grup keuangan.
- M-040 migrasi `organisasi` + `organisasi_anggota` (unik pair) + model;
  Livewire `Organisasi/Kelola` (CRUD + tambah anggota per NIM idempoten +
  toggle aktif + AuditLog) di grup admin (`/admin/organisasi`).
- M-042 migrasi `hibah_riset` + `hibah_pencairan` + model (`totalCair()`);
  Livewire `Riset/Hibah` (CRUD + catat pencairan + AuditLog) di grup dosen.
- M-043 migrasi `publikasi` (jenis/doi/status + index dosen) + model;
  Livewire `Riset/Publikasi` owner-scope (dosen kelola miliknya; hapus orang
  lain → 403; staf penuh) di grup dosen.
- M-044 migrasi `pengabdian` + model; Livewire `Riset/Pengabdian`
  (validasi tanggal_selesai ≥ mulai) di grup dosen.
- Tes: `tests/Feature/WaveBBatch3Test.php` (8 tes).

## 2026-11-15 — WAVE B batch 4: 5 item selesai, 265/265 hijau

Kode (vertical slice: migrasi + model + service/Livewire + blade + rute + tes):
- M-025/P4 `WaliList::$krsStatus` → joinSub MAX(id) per mahasiswa (1 query).
- M-029 migrasi `forum_thread` (+disematkan/terkunci) + `forum_balasan` + model;
  Livewire `Lms/Forum` (buat thread, balas, kunci/buka dosen; thread terkunci
  tolak balasan via error validasi); blade + rute `/lms/forum` (semua login).
- M-034 migrasi `pemasok` + `hutang_dagang` (unik nomor) + model (`sisa()`);
  `HutangService::bayar()` (lock, cap sisa, status bertahap, lunas idempoten);
  Livewire `Keuangan/Hutang` (+AuditLog) di grup keuangan.
- M-036 migrasi kolom penyusutan di `aset` + `AsetService` (garis lurus,
  idempoten/bulan, nilai buku ≥ 0) + `aset:susutkan` (terjadwal bulanan).
- M-039 migrasi `lowongan_kerja` + `lamaran_kerja` (unik lowongan+email) +
  model (`masihBuka()`); Livewire admin `Rekrutmen/Kelola` (grup admin) +
  publik `Rekrutmen/Lamar` (tanpa RequiresRoles — komponen publik sejati,
  pola ini dikoreksi dari asumsi awal soal DaftarPublik) di `/karier`.
- Tes: `tests/Feature/WaveBBatch4Test.php` (5 tes).

## Berikutnya (Wave B batch 5 + Wave C, belum dieksekusi)

1. Wave A M-001..M-004 (callback & lock keuangan) + tes red-green.
2. Wave A M-007..M-010 (IPS/IPK) + tes.
3. Wave A M-015 (Policy pertama: Tagihan + KHS) sebagai pola migrasi `can:`.
