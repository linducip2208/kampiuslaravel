# Audit Database — kuliah (MySQL `kuliah`)

Tanggal: 2026-11-12. Sumber: `database/migrations/*.php` (32 file) + `app/Models/*.php` (36 file) + controller/service terkait (`BayarController`, `KwitansiController`, `KrsController`, `KhsController`, `TranskripController`, `PmbController`, API V1, `KeuanganService`, `PaymentService`, `SuratNomorService`, Livewire `Krs/Pengajuan`, `Nilai/InputNilai`, `Pmb/Verifikasi`).
Tidak ada migrasi/seed yang dijalankan. Semua rekomendasi di bawah bersifat **forward-only** (tambah migrasi baru, tanpa ubah migrasi lama).

## 1. Inventaris tabel (46 tabel user + laravel/spatie)

| Tabel | PK | FK (constrained?) | Unique | Index | Catatan |
|---|---|---|---|---|---|
| `users` | id | — | email | — | +`fcm_token` (nullable, migrasi 000007) |
| `password_reset_tokens` | email | — | — (PK) | — | bawaan Laravel |
| `sessions` | id | `user_id` nullable, **tanpa FK** | — | user_id, last_activity | bawaan Laravel |
| `permissions`, `roles`, `model_has_*`, `role_has_*` | campur | cascade (spatie) | name+guard | morph index | bawaan spatie, OK |
| `personal_access_tokens` | id | morphs tokenable | token | expires_at | Sanctum, OK |
| `fakultas` | id | — | kode | — | OK |
| `tahun_akademik` | id | — | (tahun,semester) | — | `aktif` bool default false, tanpa partial-unique "hanya 1 aktif" |
| `ruang` | id | — | — | — | OK |
| `prodi` | id | fakultas_id → cascade | kode, slug | — | slug unik tapi tidak ada generator terlihat — cek seeder/controller |
| `dosen` | id | user_id → cascade; prodi_id → cascade | nidn | — | `status` string default 'aktif' (bebas, bukan enum) |
| `matakuliah` | id | prodi_id → cascade; prasyarat_id → nullOnDelete (self-ref) | kode | — | OK |
| `kelas` | id | matakuliah_id → cascade; tahun_akademik_id → cascade; dosen_id → nullOnDelete; ruang_id → nullOnDelete | — | (FK otomatis) | `hari` string nullable, jam time nullable — bentrok jadwal tidak dicegah DB |
| `mahasiswa` | id | user_id → cascade; prodi_id → cascade; dosen_pa_id → nullOnDelete | nim | — | `status` string default 'aktif' |
| `pmb_gelombangs` (plural!) | id | tahun_akademik_id **kondisional** (hanya bila tabel ada saat migrate) | — | tahun_akademik_id | kuota/biaya unsigned default 0; `aktif` bool |
| `pmb_pendaftars` (plural!) | id | user_id → nullOnDelete; gelombang_id → nullOnDelete; prodi1/2_id **kondisional** | no_daftar | email, prodi1/2, gelombang | `status` string(20) default 'diajukan'; `berkas_json` json nullable; email **hanya index, tidak unique** |
| `krs` | id | mahasiswa_id **kondisional** cascade; tahun_akademik_id **kondisional** nullOnDelete | (mahasiswa_id, tahun_akademik_id) | keduanya + status | `status` string default 'draft'; total_sks denormalisasi; ipk_snapshot decimal(3,2) nullable |
| `krs_detail` (singular!) | id | krs_id → cascade; kelas_id **kondisional** nullOnDelete | (krs_id, kelas_id) | keduanya | `kelas_id` **nullable** (lihat §3); status string default 'aktif' |
| `pertemuan` | id | `kelas_id` unsigned, **TANPA FK** | qr_token; (kelas_id, ke) | kelas_id | window_mulai/selesai + pin nullable |
| `materi` | id | `pertemuan_id` unsigned, **TANPA FK** | — | pertemuan_id | — |
| `tugas` | id | `kelas_id`, `pertemuan_id` nullable unsigned, **TANPA FK** | — | keduanya | — |
| `tugas_kumpul` | id | `tugas_id`, `mahasiswa_id` unsigned, **TANPA FK** | (tugas_id, mahasiswa_id) | keduanya | nilai decimal(5,2) nullable |
| `quiz` | id | `kelas_id` unsigned, **TANPA FK** | — | kelas_id | durasi default 60 |
| `quiz_soal` | id | `quiz_id` unsigned, **TANPA FK** | — | quiz_id | tipe string default 'pg'; kunci_jawaban char(5) nullable |
| `quiz_jawaban` | id | `quiz_soal_id`, `mahasiswa_id` unsigned, **TANPA FK** | (soal, mhs) `uq_quiz_jawaban_soal_mhs` | keduanya | skor nullable |
| `absensi` | id | `pertemuan_id`, `mahasiswa_id` unsigned, **TANPA FK** | (pertemuan, mhs) | keduanya | status string(10) default 'hadir' |
| `jurnal_dosen` | id | `pertemuan_id` unsigned unique, **TANPA FK** | pertemuan_id (via unique) | — | 1 jurnal : 1 pertemuan |
| `nilai` | id | `mahasiswa_id`, `kelas_id` unsigned, **TANPA FK** | (mhs, kelas) | keduanya | 5 kolom decimal(5,2) default 0; huruf char(2) default 'E'; bobot decimal(3,1) default 0; is_locked bool |
| `edom` | id | `mahasiswa_id`, `kelas_id`, `tahun_akademik_id?` unsigned, **TANPA FK** | (mhs, kelas) | ketiganya | skor tiny default 0 |
| `skripsi_judul` | id | judul constrained di anak; `mahasiswa_id` + `pembimbing1/2_id` **TANPA FK** | — | ketiganya | status ENUM diajukan/disetujui/ditolak/revisi/sidang/lulus |
| `bimbingan` | id | judul_id → cascade; `dosen_id` nullable **TANPA FK** | — | dosen_id | status ENUM pending/disetujui/revisi; tanggal date nullable |
| `sidang` | id | judul_id → cascade; `ruang_id` nullable **TANPA FK** | — | ruang_id | jenis ENUM proposal/hasil/tutup; status ENUM terdaftar/lulus/revisi/gagal; nilai nullable |
| `sidang_penguji` | id | sidang_id → cascade; `dosen_id` **TANPA FK** | (sidang, dosen) | dosen_id | peran string default 'penguji'; nilai nullable |
| `repository` | id | `mahasiswa_id` **TANPA FK** | — | mahasiswa_id | tahun char(4) nullable; embargo bool |
| `tagihan` | id | `mahasiswa_id`, `tahun_akademik_id?` **TANPA FK** | (mhs, tahun, jenis) `tagihan_mhs_tahun_jenis_unique` | keduanya | jenis ENUM UKT/SPP/PMB/lain; nominal/terbayar unsigned; status ENUM belum_bayar/sebagian/lunas |
| `pembayaran` | id | tagihan_id → cascade; `verified_by` nullable **TANPA FK** | reference (nullable unique) | verified_by | metode string(30); status ENUM pending/verified/rejected; reference+va_number dari migrasi 000005 (nullable) |
| `surat` | id | `mahasiswa_id` **TANPA FK** | nomor_surat (nullable unique) | mahasiswa_id | jenis ENUM; status ENUM diajukan/diproses/selesai/ditolak |
| `pengumuman` | id | `prodi_id` nullable **TANPA FK** | — | prodi_id | target ENUM semua/mahasiswa/dosen; +cover_path nullable (000003) |
| `audit_logs` | id | `user_id` nullable **TANPA FK** | — | user_id | **tanpa `updated_at`** (hanya created_at nullable); model+model_id **tanpa index**; diff_json nullable |
| `settings` | key (string PK) | — | — (PK) | — | value text nullable |
| `notifikasi` | id | user_id → cascade | — | (FK otomatis) | dibaca_at nullable |

## 2. Temuan utama

### A. FK hilang / tidak di-constrain (risiko orphan) — KRITIS
1. **Seluruh modul LMS tanpa FK**: `pertemuan.kelas_id`, `materi.pertemuan_id`, `tugas.*`, `tugas_kumpul.*`, `quiz*`, `absensi.*`, `jurnal_dosen.pertemuan_id`, `nilai.*`, `edom.*`. Hapus `kelas/pertemuan/mahasiswa` menyisakan anak yatim. Model terkait (`Pertemuan`, `Materi`, `Tugas`, `TugasKumpul`, `Quiz`, `QuizSoal`, `QuizJawaban`, `Absensi`, `JurnalDosen`, `Edom`) juga **tanpa relasi Eloquent sama sekali**.
2. **Pola "guarded FK" tidak efektif pada fresh migrate**: `pmb_gelombangs.tahun_akademik_id`, `pmb_pendaftars.prodi1/2_id`, `krs.mahasiswa_id/tahun_akademik_id`, `krs_detail.kelas_id` hanya diberi FK `if (Schema::hasTable(...))`. Karena migrasi fondasi `2026_11_13_*` (masterdata/akademik) berjalan **setelah** migrasi `2026_11_12_*`, kondisi selalu false saat fresh migrate → **FK tidak pernah dibuat**. Perlu migrasi rekonsiliasi (§6.1).
3. **Keuangan & akademik tanpa FK**: `tagihan.*`, `pembayaran.verified_by`, `surat.mahasiswa_id`, `pengumuman.prodi_id`, `skripsi_judul.mahasiswa_id/pembimbing*`, `bimbingan.dosen_id`, `sidang.ruang_id`, `sidang_penguji.dosen_id`, `repository.mahasiswa_id`, `sessions.user_id`, `audit_logs.user_id`. Minimal `verified_by → users`, `surat/tagihan/repository → mahasiswa`, `pengumuman → prodi` layak di-constrain `nullOnDelete`/restrict sesuai kebijakan.

### B. Unique + nullable = tidak melindungi (MySQL) — TINGGI
4. `krs_detail.kelas_id` **nullable** + unique `(krs_id, kelas_id)`: MySQL menganggap `NULL != NULL` → duplikat `(krs_id, NULL)` lolos. Sama untuk `tagihan` unique `(mahasiswa_id, tahun_akademik_id, jenis)` dengan `tahun_akademik_id` nullable, dan `pembayaran.reference` / `surat.nomor_surat` nullable-unique (ini masih OK secara desain — hanya baris VA/surat-bernomor yang dijamin unik — tapi perlu ditangani di kode, lihat §4).
5. `pmb_pendaftars.email` hanya index. Validasi `unique:pmb_pendaftars,email` ada di `PmbController@store:22` tapi tanpa unique DB → balapan dua submit bersamaan bisa lolos. Saran: unique DB atau terima duplikat + verifikasi manual.

### C. Inkonsistensi status / enum — SEDANG
| Modul | Kolom | Nilai |
|---|---|---|
| krs | string(20), default draft | draft/diajukan/disetujui/ditolak (konstanta `Krs::STATUSES` — bagus) |
| pmb_pendaftars | string(20), default diajukan | draft/diajukan/diverifikasi/ditolak/lulus/tidak (`PmbPendaftar::STATUSES` — bagus; beda kosakata dgn KRS: `diverifikasi` vs `disetujui`) |
| tagihan | ENUM | belum_bayar/sebagian/lunas |
| pembayaran | ENUM | pending/verified/rejected |
| surat | ENUM | diajukan/diproses/selesai/ditolak |
| skripsi_judul | ENUM | diajukan/disetujui/ditolak/revisi/sidang/lulus |
| bimbingan | ENUM | pending/disetujui/revisi |
| sidang | ENUM | terdaftar/lulus/revisi/gagal |
| absensi | string(10) | bebas (default 'hadir', tanpa konstanta/validasi terpusat) |
| dosen/mahasiswa.status | string | bebas (default 'aktif') |
| KRS vs PMB vs Surat memakai tiga kata berbeda untuk konsep "disetujui/diverifikasi/diproses". Tidak ada bug langsung, tapi filter silang (`whereIn status`) rawan salah. ENUM MySQL juga berperilaku beda di SQLite (jadi string biasa — komentar migrasi PMB sudah menyadari ini). Rekomendasi: pertahankan string + konstanta model (seperti Krs/PmbPendaftar) dan tambahkan CHECK constraint saat DB mendukung, atau samakan kosakata.

### D. Denormalisasi / derivasi — SEDANG
- `tagihan.terbayar` = sum pembayaran verified. Benar selama semua mutasi lewat `KeuanganService::verifyPembayaran` (transaksional, §4) — tetapi `PaymentService::settleByReference` menulis jalur yang sama **tanpa transaksi/lock**.
- `krs.total_sks` = sum `krs_detail.sks` (ditulis di `Livewire/Krs/Pengajuan@ajukan:140-162` dalam transaksi — OK). `krs_detail.sks` sendiri snapshot dari `matakuliah.sks` — bagus untuk cetak.
- `nilai.na/huruf/bobot` dihitung di model (`Nilai::recalculate/hitungNa/konversiHuruf`) — bagus; tapi `InputNilai@save` menulis tanpa transaksi dan tanpa cek `is_locked` yang terkunci di DB (cek di PHP saja).
- `tahun_akademik.aktif` tanpa partial-unique → dua tahun bisa aktif bersamaan; `t polarized` diandalkan `tahunAktifId()` (`Pengajuan@196-206`).

### E. Model: fillable vs kolom, casts, relasi
- **Fillable ≈ kolom**: semua model yang diperiksa fillable-nya selaras dengan migrasi (termasuk `reference/va_number` di `Pembayaran`, `cover_path` di `Pengumuman`). Tidak ada kolom migrasi yang hilang dari fillable pada jalur tulis utama. `User` memakai atribut `#[Fillable]` (gaya Laravel 11+) — OK.
- **Casts hilang**: `Tagihan.{nominal,terbayar}` tanpa cast integer (banding int di `sisa()`/`verify` masih aman karena cast manual `(int)`); `Edom.skor` tanpa cast; `SkripsiJudul.status`/`Surat.*` tanpa cast tanggal/boolean — minor. `TahunAkademik.aktif`, `Pembayaran.verified_at`, `Absensi.scanned_at` sudah benar.
- **Klarifikasi atas hipotesis awal**: `Nilai->mahasiswa` **ADA** (`app/Models/Nilai.php:71-74`) beserta `Nilai->kelas`. Yang hilang justru sisi sebaliknya dan modul LMS (detail di §5).
- **Nama relasi tidak konvensional** (fungsional, tapi membingungkan): `Prodi::mahasiswa/dosen/matakuliah`, `Dosen::kelas`, `Fakultas::prodi`, `TahunAkademik::kelas`, `Ruang::kelas`, `Matakuliah::kelas` — hasMany dalam bentuk singular (konvensi: plural).

## 3. Relasi Eloquent yang hilang (model tanpa metode padahal kolom FK ada)

| Model | Kolom FK | Relasi yang disarankan |
|---|---|---|
| Pertemuan | kelas_id | `kelas(): belongsTo`, `materis(): hasMany`, `absensis(): hasMany`, `jurnal(): hasOne`, `tugas(): hasMany` |
| Materi | pertemuan_id | `pertemuan(): belongsTo` |
| Tugas | kelas_id, pertemuan_id | `kelas/pertemuan: belongsTo`, `kumpulans: hasMany` |
| TugasKumpul | tugas_id, mahasiswa_id | `tugas/mahasiswa: belongsTo` |
| Quiz / QuizSoal / QuizJawaban | kelas/quiz/soal/mhs | rantai `quiz.soal.jawaban` + `jawaban.mahasiswa` |
| Absensi / JurnalDosen / Edom | — | `pertemuan/mahasiswa/kelas/tahunAkademik: belongsTo` |
| Mahasiswa (hanya user/prodi/dosenPa) | — | `krs, nilais, tagihans, surats, absensis, tugasKumpuls, quizJawabans, skripsiJuduls, user` balikannya |
| Kelas (hanya mk/thn/dosen/ruang) | — | `krsDetails, nilais, pertemuans, tugass, quizzes, edoms: hasMany` |
| Krs | — | `details/mahasiswa/tahunAkademik` ADA — OK |
| Dosen | — | tambah `sidangPengujis`, `bimbingans` |
| Tagihan/Pembayaran/Surat/Repository/Pengumuman | — | sisi belongsTo ADA; tambah `Tagihan.pembayarans` ADA — OK; kurang `Pembayaran.verifier` |
| audit_logs | user_id, model+model_id | `user(): belongsTo` ADA; `model/model_id` polimorfik tanpa index — tambah index atau jadikan morph |

## 4. Integritas transaksi keuangan & idempotency — TINGGI

- `KeuanganService::verifyPembayaran` (`app/Services/KeuanganService.php:73-95`): **teladan** — `DB::transaction` + `lockForUpdate` pada pembayaran & tagihan. **Celah**: tanpa guard idempotency — memverifikasi pembayaran yang sudah `verified` akan **menambahkan `terbayar` dua kali**. Tambahkan `if ($pembayaran->status === 'verified') return;` di dalam transaksi (lihat §6.4).
- `PaymentService::settleByReference` (`app/Services/Payment/PaymentService.php:82-100`): **TIDAK dalam transaksi, tanpa lock**. `BayarController@callback:67` (webhook) + `syncStatus:112` (polling) + verifikasi manual dapat mengeksekusi bersamaan → kredit ganda. Guard `status === 'verified'` di baris 86 membantu tapi TOCTOU. Wajib bungkus `DB::transaction` + `lockForUpdate` (lihat §6.4).
- `PaymentService::createVa` (`:51-78`): nominal selalu penuh (`tagihan.nominal`), tidak ada pencegahan duplikat VA (spam tombol → banyak baris `pending`). `va_number` tidak unique. Rekomendasi: tolak bila sudah ada `pending` untuk tagihan yang sama, atau reuse.
- `SuratNomorService::generate` (`app/Services/SuratNomorService.php:14-22`): `lockForUpdate()->count()` + 1 di dalam transaksi **tidak menahan phantom row**; dua request bersamaan dapat menghasilkan nomor sama → `unique nomor_surat` melempar 500. Rekomendasi: tangkap `QueryException` 1062 dan retry (§6.5).
- `Pmb\Verifikasi@daftarUlang` (`app/Livewire/Pmb/Verifikasi.php:89-101`): transaksi + `firstOrCreate` email — OK; tabrakan email bersamaan mengandalkan unique `users.email`.
- `Krs\Pengajuan@ajukan` (`app/Livewire/Krs/Pengajuan.php:140-162`): transaksi `updateOrCreate` KRS + hapus + buat ulang detail — OK. Catatan: `details()->delete()` + `create` dalam loop tanpa validasi `kelas_id` milik tahun akademik yang sama (SKS diambil dari join, tapi kepemilikan tidak dicek).
- `generateNoDaftar` (`PmbController@store` → `:65-72`): loop `exists` tanpa transaksi — tabrakan kecil kemungkinannya; `no_daftar` unique menjadi jaring pengaman (akan 500, bukan duplikat).
- `KeuanganService::generateMassal` (`:45-66`): `exists` + `create` per mahasiswa (chunk 200) — unique `(mhs,tahun,jenis)` menjadi pengaman; kegagalan di tengah chunk menyisakan sebagian (tanpa transaksi global — dapat diterima untuk job massal, tapi catat `created/skipped`).

## 5. N+1 & eager-loading

| Lokasi | Gejala | Tingkat |
|---|---|---|
| `TranskripController@cetak:12` (`app/Http/Controllers/TranskripController.php`) | `Nilai::where(mahasiswa)->orderBy(kelas_id)->get()` tanpa `with`; IPK = `avg('bobot')` **tanpa bobot SKS** (beda dengan `Api/V1/NilaiController@ringkas:34-45` yang SKS-weighted + `with kelas.matakuliah`) → inkonsistensi + N+1 bila view menyentuh `kelas/matakuliah` | Tinggi (kebenaran + N+1) |
| `KhsController@show:12` | pola sama tanpa `with`; `$totalSks = 0` tidak dihitung | Sedang |
| `KrsController@cetak:16` | `$krs->load('details')` saja; view `krs/cetak.blade.php:27-28` saat ini hanya memakai `kelas_id` (aman), tapi kebutuhan nyata (nama MK/SKS/dosen) akan N+1. Sejajarkan dengan API `with(['details.kelas.matakuliah'])` (`Api/V1/KrsController:27`) | Rendah sekarang, Tinggi saat view diperkaya |
| `BayarController@index:21,25` | `Tagihan::with('tahunAkademik')` baik; `Pembayaran::whereIn(tagihan)->...->get()` tanpa `with('tagihan')`; `createVa:36-39` lazy `$tagihan->mahasiswa->user` | Rendah |
| `Api/V1/BimbinganController@index:27-29` | `pluck(judulIds)` + `whereIn->get()` tanpa `with('judul','dosen')` | Sedang |
| `Api/V1/TugasController@index:22` | `Tugas::latest()->limit(20)->get()` tanpa with + tanpa scoping kelas/mahasiswa | Sedang |
| `Api/V1/TagihanController@index:34` | tanpa `with('tahunAkademik','pembayarans')` bila resource mengaksesnya | Rendah |
| `Livewire/Nilai/InputNilai@save:48-70` | **query dalam loop**: `value('is_locked')` + `updateOrCreate` per baris, tanpa transaksi | Sedang (skala kelas, bukan halaman publik) |
| `KeuanganService::generateMassal:45-66` | `exists` per mahasiswa dalam chunk — N query tulis; masih wajar untuk job admin | Rendah |
| `DashboardController:30` | 8× `count()` tabel — OK, bukan N+1 | OK |

## 6. Rekomendasi migrasi aman (forward-only, satu migrasi per kelompok)

> Semua migrasi: `php artisan make:migration <nama>`, hanya `Schema::table` + index/FK, **tanpa** `Schema::create` ulang / tanpa ubah file lama. Jalankan `SHOW CREATE TABLE <t>` dulu untuk konfirmasi FK guarded yang hilang.

### 6.1 Rekonsiliasi FK yang hilang (guarded-FK gap)
```php
Schema::table('pmb_gelombangs', fn (Blueprint $t) =>
    $t->foreign('tahun_akademik_id')->references('id')->on('tahun_akademik')->nullOnDelete());
Schema::table('pmb_pendaftars', fn (Blueprint $t) => [
    $t->foreign('prodi1_id')->references('id')->on('prodi')->nullOnDelete(),
    $t->foreign('prodi2_id')->references('id')->on('prodi')->nullOnDelete(),
]);
Schema::table('krs', fn (Blueprint $t) => [
    $t->foreign('mahasiswa_id')->references('id')->on('mahasiswa')->cascadeOnDelete(),
    $t->foreign('tahun_akademik_id')->references('id')->on('tahun_akademik')->nullOnDelete(),
]);
Schema::table('krs_detail', fn (Blueprint $t) =>
    $t->foreign('kelas_id')->references('id')->on('kelas')->nullOnDelete());
```
Dahului dengan pembersihan orphan (`LEFT JOIN ... WHERE parent.id IS NULL`) agar `ALTER` tidak gagal.

### 6.2 FK LMS & akademik bertahap (nullable + backfill dulu)
Tambahkan `constrained`/`nullOnDelete` untuk: `pertemuan.kelas_id → cascadeOnDelete`, `materi.pertemuan_id → cascade`, `tugas.* → nullOnDelete`, `tugas_kumpul.* → cascade`, `quiz_soal/quiz_jawaban/absensi/nilai/edom → cascade`, `jurnal_dosen.pertemuan_id → cascade`, `tagihan.mahasiswa_id → restrict`, `surat.mahasiswa_id → cascade`, `sidang_penguji.dosen_id → restrict`, `pembayaran.verified_by → nullOnDelete`, `audit_logs.user_id → nullOnDelete`. Satu migrasi per 2–3 tabel agar rollback mudah.

### 6.3 Index yang disarankan
- `pembayaran(tagihan_id, status)`, `tagihan(status)`, `krs(status)`, `surat(status, jenis)`, `sidang(status, jenis)`, `bimbingan(judul_id, status)`, `notifikasi(user_id, dibaca_at)`, `pengumuman(published_at)`, `pmb_pendaftars(status)`, `quiz_jawaban(mahasiswa_id)`.
- `audit_logs(model, model_id)` + `audit_logs(action, created_at)` — tabel ini append-only dan dibaca untuk forensik.
- `tahun_akademik(aktif)` bila sering `where('aktif', true)`.

### 6.4 Perbaikan transaksi (ubah kode, tanpa migrasi)
- `PaymentService::settleByReference`: bungkus `DB::transaction` + `lockForUpdate` pada pembayaran & tagihan; guard `verified` **di dalam** transaksi.
- `KeuanganService::verifyPembayaran`: tambah guard idempotency yang sama; tolak transisi `rejected → verified` tanpa jejak (atau catat di `audit_logs`).
- `PaymentService::createVa`: tolak/reuse bila ada `pending` aktif untuk tagihan yang sama.
- `SuratNomorService::generate`: retry ≤3× atas `QueryException` duplicate `nomor_surat`.

### 6.5 Perbaikan nullable-unique & konsistensi
- `krs_detail.kelas_id`: buat `NOT NULL` (migrasi: backfill/delete NULL → `->nullable(false)->change()` butuh doctrine/dbal) **atau** minimal validasi `required` di `Pengajuan` (saat ini hanya `integer|min:1`, lolos null? — `kelasDipilih.*` required integer, jadi NULL dari UI tertolak; celah hanya via API langsung).
- `tagihan.tahun_akademik_id`: pertimbangkan `NOT NULL` + tagihan PMB memakai tahun aktif; atau ganti unique menjadi `(mahasiswa_id, jenis)` + index parsial bila DB mendukung.
- Samakan kosakata status atau dokumentasikan matriks di `docs/`; tambah konstanta `STATUSES` mengikuti pola `Krs`/`PmbPendaftar` untuk `Tagihan`, `Pembayaran`, `Surat`, `Sidang`.
- `TranskripController`/`KhsController`: gunakan rumus IPK SKS-weighted yang sama dengan `NilaiController@ringkas` + `with(['kelas.matakuliah'])`.

### 6.6 Normalisasi opsional (ditunda, tidak mendesak)
- Jadwal bentrok `kelas(dosen_id/ruang_id, hari, jam_mulai/selesai)` hanya di aplikasi; constraint DB untuk range-overlap tidak portabel — cukup index `(ruang_id, hari)` + `(dosen_id, hari)`.
- `tahun_akademik.aktif`: enforce "tepat satu aktif" di service (transaksi) alih-alih partial unique agar tetap SQLite-kompatibel.
