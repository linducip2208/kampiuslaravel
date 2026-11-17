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

## 2026-11-15 — WAVE B batch 5: 2 item XL selesai, 272/272 hijau

Kode:
- M-028 migrasi `quiz_attempt` + kolom `opsi` di `quiz_soal`; model
  `QuizAttempt` + relasi `Quiz::{kelas,soal,attempt}`, `QuizJawaban::soal`;
  `QuizService::{mulai,resume-idempoten,sisaDetik,simpanJawaban-autosave,
  selesai-nilai-PG-otomatis,koreksi-manual,imporDariBank}`; Livewire
  `Lms/KuisKelola` (buat, impor bank, hasil, koreksi esai) +
  `Lms/KuisKerjakan` (mulai/autosave/submit, owner strict dari akun sendiri);
  rute `/lms/kuis` (dosen) + `/lms/kuis-kerjakan` (semua login).
- M-038 migrasi `payroll_periode` (unik) + `payroll_slip` (unik periode+user);
  `PayrollService::{total,simpanSlip,kunciPeriode,totalPeriode}` (final
  terkunci); Livewire `Keuangan/Payroll` di `/keuangan/payroll`.
- Tes: `tests/Feature/WaveBBatch5Test.php` (7 tes).

## 2026-11-15 — WAVE C batch 1: 4 item selesai, 277/277 hijau

Kode:
- M-050 migrasi `akreditasi_dokumen` (unik? tidak — per prodi+kategori+judul
  boleh ganda; index prodi+status) + model + Livewire `Admin/Akreditasi`
  (CRUD + verifikasi + AuditLog implisit via verifikasi timestamp) di
  `/admin/akreditasi`.
- M-051 migrasi mode/token/acak di `quiz` + `urutan_soal` di `quiz_attempt`;
  `QuizService::mulai($quiz,$mhs,$token)` (token wajib bila diset;
  kunci urutan acak saat attempt dibuat), render `KuisKerjakan` hormati
  urutan attempt; `sealKedaluwarsa()` + `kuis:seal-kedaluwarsa` (jadwal
  per jam); form kelola CBT + input token peserta.
- M-058 migrasi `ai_kuota` (unik user+tanggal+jenis) + `AiKuotaService`
  (batas config `ai.batas_harian`, tarif `ai.tarif`, `boleh()`/`catat()`
  dengan `whereDate` — temuan: where exact gagal di SQLite karena kolom
  date tersimpan 'Y-m-d H:i:s'; diperbaiki + dibuktikan tes) + hook 429 di
  `AiController@chat`.
- M-060 `public/manifest.webmanifest` + `public/sw.js` (cache shell +
  fallback `/offline`) + meta theme-color + registrasi SW di layout Tabler +
  rute `/offline`.
- Tes: `tests/Feature/WaveCBatch1Test.php` (5 tes).

## 2026-11-15 — WAVE C batch 2: 4 item selesai, 281/281 hijau

Kode:
- M-053 Livewire `Bkd/Kelola` (dosen ajukan + sinkron mengajar; admin
  setujui/tolak + AuditLog + terbitkan SK via `BkdService::generateSk`;
  rekap kategori + total SKS) di `/bkd`.
- M-055 Livewire `Alumni/Karier` (owner update pekerjaan/instansi/tracer-flag)
  di `/alumni/karier`; tracer publik `Tracer/Isi` (verifikasi NIM alumni,
  idempoten per mahasiswa) di `/tracer`; Livewire `Admin/Tracer`
  (rekap status + filter) di `/admin/tracer`.
- M-056 `EksekutifService::kpi()` (IPK rata, lulusan, serapan tracer, rasio,
  realisasi anggaran — semua agregat) + Livewire `Analitik/Eksekutif`
  di `/laporan/eksekutif`.
- M-059 kontrak Flutter dibuktikan `WaveCBatch2Test::test_kontrak_api_flutter`
  (14 endpoint: shape stabil, validasi 422, 404 tak dikenal, webhook 503,
  OpenAPI 3.1) — tanpa ubah prod.
- Tes: `tests/Feature/WaveCBatch2Test.php` (4 tes).

## 2026-11-15 — WAVE C batch 3: 2 item XL selesai, 285/285 hijau

Kode:
- M-049 migrasi `cpl` (unik prodi+kode) + `cpmk` (unik MK+kode, bobot) +
  `cpmk_asesmen` (unik cpmk+tipe+sumber, tipe quiz_soal|tugas) + model/relasi;
  `ObeService::ketercapaian()` (skor kuis = skor/bobot, tugas = nilai/100,
  terbobot per CPMK→CPL, hanya asesmen yang dikerjakan); Livewire
  `Obe/Kelola` (CRUD CPL/CPMK + pemetaan + rekap per NIM) di `/admin/obe`.
- M-052 migrasi `coa` + `jurnal_umum` + `jurnal_item`; `GlService::posting()`
  (xor-sisi per baris, ≥2 baris, seimbang, akun aktif, guard tutup-buku),
  `::bukuBesar()` (saldo berjalan sisi normal), `::neracaSaldo()`,
  `::seederDasarCoa()` (8 akun); Livewire `Keuangan/Gl` (CoA + jurnal
  dinamis + neraca + buku besar) di `/keuangan/gl`.
- Tes: `tests/Feature/WaveCBatch3Test.php` (4 tes).

## 2026-11-15 — WAVE C batch 4: RAG + SCORM selesai, 289/289 hijau.
Wave B 28/28 SELESAI. Tersisa hanya item BLOCKED (M-054 + Wave D).

Kode:
- M-057 migrasi `knowledge_base` + `knowledge_chunk`; `RagService::{ingest
  (chunking ±800 char), jawab (retrieval skor kata kunci + driver AI +
  redaksi PII + catat AiJob + kuota rag)}`; `RagGuardrail::{saringMasukan
  (blocklist injeksi), redaksiPii (NIK/HP/email), skor, relevan}`;
  `POST /api/v1/ai/rag` (throttle 30,1 + kuota 429); tarif rag di config.
  Temuan: `where('tanggal',...)` gagal di SQLite (kolom date tersimpan
  'Y-m-d H:i:s') — wajib `whereDate` (portabel MySQL+SQLite).
  Temuan 2: skor retrieval wajib strip tanda baca (`dilaksanakan?`).
- M-030/031 `ScormService::paket()` — ZIP per pertemuan berisi berkas materi
  + `imsmanifest.xml` (SCORM 1.2) + `metadata.json`; rute
  `GET /lms/paket/{pertemuan}` (semua login, throttle 10,1).
- Tes: `tests/Feature/WaveCBatch4Test.php` (4 tes).

## 2026-11-15 — Wave D readiness + M-054 desain: 293/293 hijau

Bukan kode integrasi pura-pura — yang dikerjakan jujur bisa diuji:
- M-054: `docs/DESAIN_MULTI_KAMPUS.md` (Opsi A shared-schema+kampus_id
  direkomendasikan, matriks permission, resolusi tenant, migrasi 5 fase +
  rollback, checklist pengesahan). [SUPERSEDED 2026-11-15: M-054 DITOLAK
  per keputusan single-institution; lihat entri RBAC batch 1.]
- M-064: `Mail/TagihanReminderMail` + `Services/MailService`
  (`terkonfigurasi()`: smtp+host, selain itu no-op) + hook di
  `tagihan:ingatkan` (ikut idempotensi H-7/HP7, try/catch report).
  FCM sudah ada sebelumnya (token + graceful skip + NotifLog).
- `docs/INTEGRASI.md` §11: matriks kesiapan Wave D per item
  (adapter vs kredensial yang kurang).
- Tes: `tests/Feature/WaveDReadinessTest.php` (4 tes).

## 2026-11-15 — Landing page ala konsep visual (tanpa klaim palsu), 297/297 hijau

- `welcome.blade.php` ditulis ulang mengikuti 7 poin konsep: hero terang +
  mockup dashboard dominan (angka real `$stats` + grafik SVG),
  strip kepercayaan kategori PT (tanpa logo universitas fiktif),
  8 modul ringkas, mengapa + alur 4 tahap, grid 10 fitur SVG,
  band statistik angka real, testimoni WAN + label ilustrasi,
  CTA banner foto kampus, footer navy + tombol ke atas.
- Nav publik: Beranda/Fitur/Modul/Keamanan/FAQ/Dokumentasi + Masuk/Request Demo.
- Tes: `tests/Feature/LandingTest.php` (4 tes: 7 elemen, tanpa klaim palsu,
  angka ikut DB, nav jangkar).
- Aset 100% lokal (ComfyUI WAN): server ComfyUI sedang mati saat eksekusi,
  jadi dipakai ulang `kampus-hero-wan.png` + avatar WAN yang sudah ada —
  tanpa unduh gambar eksternal.

## 2026-11-15 — Utang teknis P5 + P8 selesai, 292/292 hijau (Selesai tetap 60/66: sub-bagian M-025)

- P5 (`Api Jadwal:28-30`): tanpa scope mahasiswa, dosen dibatasi kelasnya
  sendiri, peran lain dibatasi 200 baris — bentuk respons tetap array
  (kontrak mobile tidak pecah, diverifikasi `MobileContractTest` implisit
  via suite penuh).
- P8: koreksi lokasi — `va_reference` ternyata di `pmb_pendaftars`, bukan
  `pembayaran` (BACKLOG/M-013 & PERFORMANCE_REPORT §B/§D salah menunjuk
  tabel — dikoreksi + bukti tes). Migrasi unique
  `pmb_va_reference_unique` + guard duplikat eksplisit.
- P2/P6/P7: P2 ditunda sadar (batch admin), P6/P7 diterima dengan alasan
  tercatat di PERFORMANCE_REPORT §E.

## 2026-11-15 — Budget query anti-N+1 terukur, 303/303 hijau

- `tests/Feature/PerformanceBudgetTest.php` (3 tes): KHS & transkrip
  query TETAP pada 5 vs 60 baris nilai (19–21, plafon 25) + `validasiKrs`
  ≤ 12 query untuk 10 kelas. Hasil ukur dicatat di
  MEGA_PERFORMANCE_REPORT §E. Bukan klaim p95/skala produksi.

## 2026-11-15 — P2 generate batch selesai + README asli, 304/304 hijau

- `JadwalService::generate()` preload 1x jadwal terisi, cek overlap
  in-memory (predikat identik versi DB): 8 kelas = 3 baca + 8 tulis.
  Uji tanpa bentrok hasilnya + plafon 30 query.
- `README.md` generik Laravel diganti dokumentasi proyek asli
  (setup, tes, struktur, status jujur 60/66).
- Tes: +1 di `tests/Feature/PerformanceBudgetTest.php` (4 tes).

## 2026-11-15 — Smoke test server nyata (MySQL dev), 10/10 endpoint 200

`php artisan serve` + curl ke MySQL lokal (DB terisi: 6 users, 1 mhs):
`/health` ok/0-pending, `/`, `/login`, `/api/openapi.json`,
`/offline`, `/karier`, `/tracer`, `/manifest.webmanifest`, `/sw.js`,
`/api/v2/status` (db up, 0 pending) — semua 200. Blade terkompilasi
penuh (`view:cache` lolos). Bukan bukti browser/E2E — tetap 0.

## 2026-11-15 — RBAC batch 1 + Tabler lokal + pensiun M-054 (target: suite hijau)

- M-054: DITOLAK_WITH_EVIDENCE per keputusan arsitektur single-institution
  (brief master §1-6); `DESAIN_MULTI_KAMPUS.md` jadi arsip bertanda
  JANGAN-dieksekusi; larangan kode isolasi tenant.
- Frontend: Tabler 1.7.5 di-vendor lokal (`public/vendor/tabler-1.7.5/`,
  tercatat di package.json) — CDN `@latest` dihapus dari layout +
  halaman login; tanpa ubah tampilan (aset identik).
- RBAC: 13 peran baru di seeder (aditif; 6 lama utuh, teruji),
  `UserData::daftarRole()` dinamis dari DB, `docs/FINAL_RBAC_MATRIX.md` v1,
  `tests/Feature/RbacExpansionTest.php` (4 tes: matriks, regresi,
  penetapan via UI, gate permission vs denial).
- Tes WaveDReadiness yang basi diperbarui mengikuti status DITOLAK.

## 2026-11-15 — RBAC batch 2 SELESAI: wiring peran + IDOR, 316/316 hijau

- Grup rute: dosen +`kaprodi|kajur`, keuangan +`kasir|akuntansi`,
  pimpinan +`rektor|warek|dekan|wadek`.
- Komponen Livewire (`allowedRoles`) ikut diperbarui (21 file) — rute saja
  memberi rasa aman palsu (terbukti: kaprodi lolos middleware tapi 403 di
  komponen sebelum fix).
- `pddikti.push` kini menulis AuditLog (eksekutif baru boleh push).
- BKD + riset: akun tanpa baris dosen ditolak (anti null===null IDOR).
- `AuthorizesMahasiswaData`: staf = +kaprodi/kajur/rektor/warek.
- Tes: `RbacWiringTest` 8 tes (matriks izin/tolak, IDOR menang atas peran,
  audit push, BKD anti-bocor).

## 2026-11-15 — RBAC batch 3 SELESAI: examiner + HR + matriks + audit, 320/320 hijau

- Jalur examiner mandiri: `Sidang/NilaiPenguji` (owner-scope ketat,
  tanpa akses NilaiSidang penuh) di `/sidang/nilai-saya` (grup
  `role:penguji|superadmin|admin_prodi`).
- HRD: grup `role:hrd|...` dengan `/hrd/payroll` + `/hrd/rekrutmen`
  (bukan grup keuangan penuh — SoD); komponen ikut dibuka untuk hrd.
- Matriks peran baca-saja: `Admin/MatriksRbac` di `/admin/matriks-rbac`.
- Audit penugasan: `UserData::simpan` catat `user.role.diubah` + diff.
- Tes: `RbacBatch3Test` 4 tes (owner-scope penguji, HRD terbatas, matriks,
  audit diff). Perilaku Livewire: `firstOrFail` gagal jadi respons 404,
  bukan exception — asersi = data tak berubah.

## 2026-11-15 — RBAC batch 4 SELESAI + 5 ide bank tereksekusi, 333/333 hijau

RBAC: delegasi berbatas waktu (`delegasi_akses` + `delegasi:sinkron`
harian + anti-cabut-manual) + `permission:review`, scoping OBE per prodi,
UI delegasi + matriks diperbarui. Tes: `RbacBatch4Test` (7).
Ide bank (2 subagen paralel + integrasi): impor CSV bank soal
(`BankSoalImporTest`, 3), audit baca dokumen (`AuditBacaTest`, 3),
pesan denda terperinci, sidang tolak bentrok kuliah,
cek syarat wisuda `WisudaService` (pratinjau; alur `luluskan` utuh).

## 2026-11-15 — Batch 5b ide bank: kutipan forum, notif PA, kunci brute-force (target: suite hijau)

- `Forum::kutip()` mengisi draf balasan format kutip + tombol di daftar balasan.
- `Pengajuan::ajukan()` menulis notifikasi inbox ke Dosen PA (try/catch agar
  tidak menggagalkan pengajuan); ikut idempotensi pengajuan yang sama.
- `SessionAuthController@login`: kunci 15 menit setelah 5 gagal per email
  (RateLimiter 900 dtk, di atas throttle:login per IP); reset saat sukses.
- Tes: `IdeBatch5Test` (3 tes).

## 2026-11-16 — Perpustakaan sirkulasi + katalog + peran pustakawan, 341/341 hijau

- `perpus_buku` + `perpus_eksemplar` (barcode unik) + `perpus_pinjam`;
  `PerpustakaanService::{pinjam (maks 3 aktif, tx+lock), kembali
  (denda Rp1.000/hari), hitungDenda}`; Livewire `Perpus/Sirkulasi`
  (pinjam by barcode + kembali/hilang/rusak + AuditLog) di
  `/perpus/sirkulasi` (grup `pustakawan|superadmin|admin_prodi`);
  OPAC publik `Perpus/Katalog` (tanpa login) di `/katalog`;
  peran baru `pustakawan` di seeder.
- Tes: `PerpusTest` (5 tes). Catatan hitung: 341 eksekusi vs 324 metode
  `test_*` — selisih = data provider (mis. CalculationTest),
  diverifikasi via audit `count_tests.py`, bukan angka karangan.

## 2026-11-16 — Ide bank batch 6: kalender ruang, arsip KRS, buku wisuda CSV, pesan denda

- `Jadwal/KalenderRuang` (mingguan kuliah + booking disetujui/diajukan,
  geser pekan, filter ruang) di `/fasilitas/kalender` (semua login,
  baca-saja; pengajuan tetap via `PinjamRuang`).
  Temuan: `whereIn('tanggal', ...)` gagal di SQLite karena kolom date
  tersimpan `'Y-m-d H:i:s'` — wajib `whereDate` rentang (pola sama
  seperti bug `ai_kuota`).
- Arsip KRS: `Pengajuan::getRiwayatSayaProperty` (10 pengajuan terakhir
  + `tahunAkademik`) + blok "Arsip Pengajuan Saya" dengan tautan unduh
  `krs.cetak` (cetak existing dipakai ulang, tanpa PDF baru).
- Buku wisuda CSV: `Yudisium::eksporCsv` (stream CSV `;`-separated
  kursi/nim/nama/no_ijazah/pin/toga per batch, tanpa dependensi PDF)
  + tombol "Unduh CSV".
- Pengingat denda: `KeuanganService::pesanPengingat` sudah merinci denda
  (`termasuk denda RpX`) — dikunci oleh tes agar tidak regresi.
- Tes: `Batch6Test` (4 tes: kalender tampil + tamu ditolak, arsip muncul
  setelah pengajuan, CSV terunduh, pesan memuat denda).

## 2026-11-16 — Ide bank batch 7: peringatan SKS, antrean /health, notif PA at-risk

- Peringatan SKS: kolom "Sisa SKS" (`getSisaSksProperty = sksMaks -
  totalSks`) di kartu ringkasan `Krs/Pengajuan` (peringatan dini, bukan
  cuma blokir saat submit).
- `/health`: tambah `queue` + `queue_failed` + `checks.queue`
  (guard `Schema::hasTable('jobs'/'failed_jobs')`; metrik tak boleh
  menggagalkan health; status `ok` tetap berbasis migrasi).
- At-risk → PA: tombol "Kirim ke PA" per baris level tinggi di
  `Analitik/AtRisk` → `Notifikasi` inbox ke Dosen PA (try/catch).
- Perbaikan penghalang (jujur: fitur at-risk selama ini mati total):
  `daftarBerisiko` memilih kolom `nama` yang tidak ada di tabel
  mahasiswa + query `is_late` atas kolom yang belum pernah dibuat →
  exception ditelan `catch` → daftar selalu kosong. Diperbaiki via nama
  dari relasi `user` + migrasi `add_is_late_to_tugas_kumpul_table`
  (+ fillable/cast `TugasKumpul`).
- Tes: `Batch7Test` (3 tes: sisa SKS tampil, health memuat antrean,
  skor 61 → tombol + notif PA tercatat).

## 2026-11-16 — Ide bank batch 8: paket materi massal, tren tracer, kunci review permission

- LMS: `ScormService::paketKelas` — ZIP per kelas (folder per pertemuan,
  manifest gabungan, metadata jumlah pertemuan/materi) + rute
  `/lms/paket-kelas/{kelas}` (`throttle:10,1`, pola sama dgn paket
  pertemuan; `deleteFileAfterSend`).
- Tracer admin: tabel "Tren serapan per tahun lulus" (responden vs
  terserap bekerja/wirausaha + bar %) dari kolom `tahun_lulus`.
- Review permission: tanpa kode baru — `permission:review` sudah mencakup
  semua yang diminta ide; diverifikasi jalan bersih di dev + dikunci tes.
- Tes: `Batch8Test` (3 tes: isi ZIP + metadata, tren 2024 2/1/50%,
  `permission:review` exit 0).

## 2026-11-16 — Ide bank batch 9: revisi KRS, pratinjau PDF, klon bank soal

- Revisi KRS: tabel `krs_revisi` (FK cascade, total_sks, ipk_snapshot,
  rincian JSON) + snapshot state lama tiap submit ulang + tab "Riwayat
  Revisi" di halaman pengajuan. Detail kritis: state lama dibaca SEBELUM
  `updateOrCreate` (yang menimpa angka) — kalau sesudah, snapshot salah.
- Materi: tombol Pratinjau/Tutup + `<embed type="application/pdf">` +
  tautan "Buka file" (target blank); non-PDF hanya tautan.
- Bank soal: `BankSoal::klon` + dropdown "Klon ke MK" di header daftar
  + tombol Klon per baris (isi + opsi + kunci + bobot ikut).
- Tes: `Batch9Test` (3 tes: snapshot berisi kelas lama, embed tampil,
  klon → 2 baris).

## 2026-11-16 — Ide bank batch 10: masa studi, lampiran cuti, rekap BKD prodi

- Transkrip: `AkademikService::masaStudi(angkatan)` (periode aktif →
  semester-ke + label "N tahun M semester") + badge "Masa studi" di
  `transkrip.cetak`. Jujur: tanggal lulus belum (butuh kolom baru).
- Cuti: migrasi `lampiran_path` + unggah opsional (PDF/gambar ≤10MB) +
  kolom Lampiran di riwayat; histori daftar sudah ada sebelumnya.
- BKD: `BkdService::rekapPerProdi` (SKS disetujui + dosen per prodi,
  admin-only) di halaman kelola.
- Tes: `Batch10Test` (4 tes: rumus semester-7, tampil di transkrip,
  file tersimpan + tautan, agregat 6 SKS/1 dosen).

## 2026-11-16 — Ide bank batch 11: VA expiry, ranking seleksi, SK organisasi

- PMB VA: `va_expires_at` (+7 hari via `issueFor`, config
  `payment.defaults.va_expiry_days`) — reuse bila berlaku, regenerate
  bila lewat; settle menolak kedaluwarsa (log warning). Kompatibel:
  VA lama tanpa expiry dianggap berlaku.
- Seleksi: status `cadangan` (string, tanpa ubah enum DB) +
  `prosesRanking` per gelombang + filter gelombang + ambang editable +
  tombol Ranking (confirm). Menghormati kuota lulus yang sudah terisi.
- Organisasi: `nomor_sk` (input manual; otomatis `SK-ORG/tahun/id`
  bila kosong) + kolom di daftar.
- Tes: `Batch11Test` (3 tes: siklus VA lengkap, ranking kuota-1,
  SK otomatis + tampil).

## 2026-11-16 — Ide bank batch 12: arsip PMB, penguji eksternal, kuitansi gabungan

- PMB: `PmbArsipService::arsipGelombang` + rute admin + audit.
  Retensi/hapus fisik tetap manual (keputusan admin).
- Sidang: `nama_eksternal` + `dosen_id` nullable (SQLite: rebuild tabel
  + salin data; MySQL: MODIFY) + input di `NilaiSidang`. Input mandiri
  tetap dosen berakun (tanpa akun tak bisa login — batas wajar).
- Keuangan: `gabungan` + view + rute (owner-scope ikut).
- Tes: `Batch12Test` (3 tes: ZIP + manifest + unduhan, rata 80,
  total 1.500.000).

## 2026-11-16 — Ide bank batch 13: aging piutang, laba-rugi, tracer email

- Keuangan: `piutangAging` (bucket dari `hariTerlambat`, hanya sisa>0;
  total bucket = total piutang) + CSV. Catatan tes: badan stream tak
  tertangkap TestResponse → verifikasi via header + service.
- GL: `labaRugi` (per akun tipe pendapatan/beban + surplus) + kartu di
  halaman GL.
- Tracer: `tracer:ingatkan` + mailable + view; guard konfigurasi →
  dry-run. Proxy tanggal lulus = `updated_at` (jujur: kolom khusus
  belum ada).
- Tes: `Batch13Test` (3 tes: bucket + total, surplus 600rb, dry-run
  memuat NIM).

## 2026-11-16 — Ide bank batch 14: saldo awal, arus kas

- GL: `postingSaldoAwal` (jurnal SALDO-AWAL idempoten; tanpa tabel
  khusus, mengalir ke semua laporan).
- Kas: `bayar` hutang → jurnal `pengeluaran` (migrasi enum; SQLite
  rebuild + salin `deleted_at` + lepas index lama) + `arusKas`
  (masuk/keluar/bersih/bulanan) + kartu di halaman Hutang.
- Tes: `Batch14Test` (3 tes: idempoten + neraca, 2jt−800rb, skema enum).

## 2026-11-16 — Ide bank batch 15: SK mengajar, opname aset, log lambat

- SK: controller cetak + verify (QR bertanda, owner-scope, print-CSS).
- Aset: tabel + komponen opname + audit; GL penyesuaian belum otomatis.
- Observability: listener query lambat (ambang config, anti-ganggu).
- Tes: `Batch15Test` (3 tes: cetak + VALID + 403 palsu, selisih
  diterapkan, sunyi saat ambang raksasa).

## 2026-11-16 — Ide bank batch 16: RAG prodi, slip cetak, sub-CPMK

- AI: `prodi_id` di knowledge base + filter relevansi + API teruskan
  prodi penanya (kompatibel ke belakang).
- Payroll: slip cetak print-CSS + tombol per baris.
- OBE: tabel + CRUD sub-CPMK + badge di daftar.
- Tes: `Batch16Test` (3 tes: no-bocor relevance, slip tampil, SUB-1).

## 2026-11-16 — Ide bank batch 17: evaluasi RAG, API orangtua

- AI: `evaluasi` (F1 deterministik) + `rag:evaluasi` + `MAX_KONTEKS`.
- API: pivot wali + controller read-only + `wali:tautkan` (403 ketat).
- Tes: `Batch17Test` (3 tes: skor relevan, jawab panjang, siklus wali).

## 2026-11-16 — Ide bank batch 18: tunjangan template, onboarding, KHS offline

- Payroll: template tunjangan per peran + isi otomatis (manual bisa ubah).
- Rekrutmen: checklist bawaan saat diterima + toggle di tabel.
- KHS: snapshot localStorage + banner luring.
- Tes: `Batch18Test` (3 tes: auto-isi 750rb, 3 item + toggle, cache key).

## 2026-11-16 — Ide bank batch 19: asrama (kamar + iuran)

- Asrama: tabel kamar + hunian + `Kelola` (CRUD kamar, huni/keluar,
  audit, tarik iuran idempoten) di `/asrama`.
- Tagihan: jenis `asrama` (MySQL MODIFY + rebuild SQLite paritas index;
  ImporCsv ikut).
- Tes: `Batch19Test` (3 tes: ganda/penuh ditolak, iuran 750rb
  idempoten, halaman admin).

## 2026-11-16 — Ide bank batch 20: klinik + runbook restore

- Klinik batch 1: kunjungan + resep, peran/permission terpisah, gate
  ganda, audit tulis + baca. Rute sempat salah grup (dalam admin) —
  diperbaiki keluar + tes dua lapis.
- Operasi: runbook restore triwulan (manual pertama pending staging).
- Tes: `Batch20Test` (2 tes: 403 dua lapis, simpan + resep + audit).

## 2026-11-16 — Ide bank batch 21: kontrak paging API v1

- Helper `ApiPaging` + 3 endpoint disamakan (q/sort/per_page + meta).
  Perubahan kontrak dicatat jujur (tugas limit→paginasi).
- Tes: `Batch21Test` (3 kontrak).

## 2026-11-16 — Ide bank batch 22: siklus SPMI + cetak rapor

- SPMI: tabel + kartu PPEMPMTI di halaman Akreditasi (idempoten).
- Cetak: `css/cetak.css` + link print di layout (berlaku semua dokumen).
- Tes: `Batch22Test` (2 tes: idempoten + badge, link + aturan CSS).

## 2026-11-16 — Ide bank batch 23: borang + triase 2 ide berisiko

- Akreditasi: `BorangService` + ekspor CSV per prodi + tombol
  (draf, bukan klaim).
- Triase jujur: push-topik (butuh kredensial FCM) + rotasi token
  (butuh kontrak Flutter) ditandai DITUNDA, bukan dikerjakan buta.
- Tes: `Batch23Test` (1 tes: angka ringkasan + header unduhan).

## 2026-11-16 — Ide bank batch 24: empty-state + aksesibilitas (halaman baru)

- Empty-state informatif di 7 titik halaman batch 6–23 (batas cakupan
  dicatat jujur).
- Aksesibilitas: verifikasi fondasi layout + asosiasi label checkbox +
  aria-label; uji tab-only manual penuh belum dilakukan.
- Tes: `Batch24Test` (2 tes: pesan kosong tampil, asosiasi label).

## 2026-11-16 — Ide bank batch 25: push topik + rotasi token (buka 2 tertunda)

- FCM: `broadcastTopik` (topic `prodi_{id}`, guard kredensial) +
  `kirimPesanTopik` teruji deterministik + command `push:topik`.
- Auth: expiry opt-in (`MOBILE_TOKEN_EXPIRY_DAYS`, default abadi) +
  `POST /api/v1/token/refresh`. Kontrak lama utuh tanpa konfigurasi.
- Temuan uji: guard Sanctum me-memo user antar-call dalam satu test
  (RequestGuard singleton) → `forgetGuards()` sebelum asersi rotasi;
  OpenSSL Windows tak bisa buat kunci RSA (full-path FCM di-skip jujur
  di env ini).
- Tes: `Batch25Test` (4 tes: guard, payload, default abadi, rotasi).
- **Bank ide TUNTAS 60/60** — tak ada lagi item terbuka/ditunda.

## 2026-11-17 — Wave D batch 26: 5 item BLOCKED dibuka via guard jujur (5 subagen paralel)

- M-061: guard SSRF Feeder (`guard_ssrf` opt-in; tolak IP privat/reserved + audit blokir; default OFF agar kompatibel).
- M-062: refund gateway per driver (Xendit/Midtrans API, Tripay jujur manual) + checkStatus Xendit QRIS/ewallet.
- M-063: WA jujur (flag simulasi, counter tak menyesatkan, tombol tes kirim di Integrasi); 1 tes lama disesuaikan kontrak baru.
- M-065: SSO OAuth2 manual tanpa Socialite (proteksi state, link-akun saja, tombol login); tanpa ubah dependensi.
- M-066: `TteService` BSrE (guard + sign + status) teruji via Http::fake.
- Tes: 23 baru + 1 lama disesuaikan; 422 hijau + 1 skip env.
- Batas jujur: e2e nyata (Feeder kampus, gateway prod, WA gateway, Google/Entra, BSrE) tetap menunggu kredensial; tak ada klaim kirim nyata.

## 2026-11-17 — Hardening batch 27: guard-test rute, readiness command, audit forensik, OpenAPI, perf (5 subagen paralel)

- Guard: 12 rute sensitif baru dikunci di `RouteGuardTest`.
- Operasi: `integrasi:cek` (8 layanan, read-only, exit selalu 0).
- Forensik: audit refund gateway, push topik, WA nyata (nomor tersamar), login SSO.
- API: OpenAPI generate + parameter q/sort/per_page + skema Meta + test kontrak.
- Perf: eager-load terverifikasi aman, 2 index tracer baru, test batas query.
- Tes: 10 baru; 432 hijau + 1 skip env.

## Status akhir: 60/66 SELESAI lapang + 5 KODE-SELESAI-guard (M-061,062,063,065,066; e2e menunggu kredensial nyata); M-054 DITOLAK desain.
