# AUDIT ARSITEKTUR — SIMAK Kuliah

> Status: audit read-only, 12 Nov 2026. Workspace `D:\project laravel\kuliah` (Laravel 13.42, Livewire, Spatie Permission 8.5, Sanctum 4, Tabler via CDN, MySQL `kuliah`).
> Sumber: `composer.json`, `bootstrap/app.php`, `routes/web.php`, `routes/api.php`, `config/payment.php`, `config/comfyui.php`, controllers, Livewire, services, jobs, models, migrations, views, `docs/PRD.md`, `docs/PROGRESS.md`.

---

## 1. Ringkasan Arsitektur

Monolit Laravel 13 klasik + API + background job:

```
Browser (Blade + Livewire + Tabler CDN)
  -> routes/web.php (session auth, middleware `auth` global grup)
  -> Controller (cetak/PDF-view, publik, callback, PDDikti) ATAU
     Livewire component (CRUD interaktif) + Service + Eloquent Model -> MySQL
  -> AuditLog (ditulis manual di Livewire sensitif)

Mobile/Flutter (stub, belum ada folder mobile/ di repo)
  -> routes/api.php /api/v1 (Sanctum bearer, ability `mobile`)
  -> Api\V1\*Controller -> Model/Service -> JSON

Background / integrasi:
  GenerateMediaJob --ComfyUIService--> ComfyUI server (WAN 2.1, `config/comfyui.php`)
  PaymentService --adapter--> Simulated|Tripay|Midtrans|Xendit (`config/payment.php` + tabel `settings`)
  PddiktiService --HTTP--> Feeder PDDikti (kredensial di `settings`, halaman `admin.integrasi`)
  NotifikasiService --inbox DB + FCM--> mobile (`settings` fcm.*, `fcm_token` di users)
```

Pola yang dipakai:
- **Controller tipis untuk dokumen/cetak & publik/callback** (`KrsController@cetak`, `KhsController@show`, `SuratController`, `KwitansiController`, `TranskripController`, `PmbController`, `PublikController`, `BayarController`, `PddiktiController`, `DashboardController`, `SessionAuthController`).
- **Livewire untuk semua CRUD interaktif** (22 komponen: `Pmb/*`, `Krs/*`, `Nilai/*`, `Lms/*`, `Absensi/ScanQr`, `Surat/*`, `Skripsi/*`, `Sidang/*`, `Bimbingan/Log`, `Keuangan/*`, `Notifikasi/Broadcast`, `Media/Generator`, `Admin/Integrasi`).
- **Service layer untuk logika lintas-modul**: `KeuanganService` (tunggakan, generate massal, verifikasi + lock), `AbsensiService::scan`, `SuratNomorService`, `Payment/PaymentService + 4 adapter + interface`, `NotifikasiService::broadcast`, `PddiktiService`, `ComfyUIService`.
- **Satu-satunya Job**: `GenerateMediaJob` (update `pengumuman.cover_path`). **Satu-satunya Command**: `ComfyGenerateCommand` (`app/Console/Commands`), tidak diregistrasi di `routes/console.php` (hanya ada command `inspire` bawaan) — mengandalkan auto-discovery Laravel 11+.
- **Settings DB-backed** (`Setting::get/set` + `Cache::rememberForever`, tabel `settings`) untuk payment gateway, FCM, PDDikti, ComfyUI. Tidak ada hardcode kredensial di adapter.
- **Layout tunggal** `layouts/tabler.blade.php` + wrapper `pages/livewire-page.blade.php` untuk 12 route Livewire. Sidebar tidak membedakan role (semua menu tampil untuk semua user login).

---

## 2. Jenis Aplikasi: Single-Campus (bukan multi-campus / bukan SaaS)

Bukti:
- Skema `fakultas (id, nama, kode)` <- `prodi.fakultas_id` (wajib, `cascadeOnDelete`), `prodi (kode, slug unique)` — hierarki 1 universitasImplisit, tidak ada tabel `universitas`/`kampus`/`tenant`.
- Tidak ada kolom `tenant_id`, `university_id`, `campus_id`, tidak ada middleware tenancy, tidak ada scoping global per kampus. `grep tenant|campus|kampus` hanya kena string email dummy (`mahasiswa@kampus.ac.id`) dan komentar.
- `Setting` adalah key-value global tunggal (`key` PK string), tidak ada `tenant_id`/`context`. Semua integrasi (pay.*, fcm.*, pddikti.*) bersifat global.
- Role Spatie global (`superadmin, admin_prodi, keuangan, dosen, mahasiswa, pimpinan` — dari validasi `Notifikasi/Broadcast.php:21` + `BayarController` cek `keuangan`); tidak ada pemisahan data per prodi di query (mis. `KeuanganService::generateMassal` filter opsional `prodi_id`, tapi default seluruh mahasiswa aktif).
- Kesimpulan: **aplikasi SIAKAD single-campus / single-tenant**. Kalau kelak multi-kampus/SaaS, perlu tenant key di `settings`, `prodi/mahasiswa/kelas`, dan scoping + middleware baru. PRD M1 menyebut "Universitas/Fakultas/Prodi" tapi implementasi hanya Fakultas->Prodi.

---

## 3. Tabel Route Lengkap + Status

### 3a. Web (publik — tanpa `auth`)

| # | Method | URI | Handler | Name | Status |
|---|--------|-----|---------|------|--------|
| W1 | GET | `/` | closure (stats welcome) | `home` | OK publik, aman (try/catch + `Schema::hasTable`). Catatan: query agregat tiap hit |
| W2 | GET | `/login` | `SessionAuthController@showLogin` | `login` | OK publik |
| W3 | POST | `/login` | `SessionAuthController@login` | `login.attempt` | OK; tanpa throttle/rate-limit (risiko brute force) |
| W4 | GET | `/prodi` | `PublikController@prodi` | `publik.prodi` | OK publik (pSEO) |
| W5 | GET | `/prodi/{slug}` | `PublikController@prodiDetail` | `publik.prodi.detail` | OK publik |
| W6 | GET | `/pengumuman` | `PublikController@pengumuman` | `publik.pengumuman` | OK publik |
| W7 | GET | `/pengumuman/{id}` | `PublikController@pengumumanDetail` | `publik.pengumuman.detail` | OK publik; cek: pastikan hanya yang `published` (perlu verifikasi di controller) |
| W8 | GET | `/pmb` | `PmbController@create` | `pmb.create` | OK publik |
| W9 | POST | `/pmb` | `PmbController@store` | `pmb.store` | OK; tanpa honeypot/captcha/throttle — rawan spam massal |
| W10 | GET | `/pmb/cek` | `PmbController@cek` | `pmb.cek` | OK; enumerasi `no_daftar` berurutan dimungkinkan (format `PMB-YYYY-NNNNNN` random tapi tanpa rate-limit) |
| W11 | GET | `/surat/verifikasi/{nomor}` | `SuratController@verify` | `surat.verify` | OK publik (QR); info terbatas (nomor/jenis/status) — OK |
| W12 | POST | `/payment/callback` | `BayarController@callback` | `payment.callback` | OK publik by-design; CSRF dikecualikan di `bootstrap/app.php:18`; keaslian via signature adapter — **pastikan semua adapter benar-benar verifikasi signature** (risiko P0 jika Simulated lolos di prod) |

### 3b. Web (grup `auth` — **tanpa `role`/`permission` middleware sama sekali**)

> Temuan utama: seluruh 30 route auth hanya dicek `auth`. Otorisasi mengandalkan (a) cek manual di sebagian controller/service, (b) tidak ada cek di sebagian besar Livewire/view. Sidebar menampilkan semua menu ke semua role (`tabler.blade.php:50-64`).

| # | Method | URI | Handler | Name | Status |
|---|--------|-----|---------|------|--------|
| W13 | POST | `/logout` | `SessionAuthController@logout` | `logout` | OK |
| W14 | GET | `/dashboard` | `DashboardController@index` | `dashboard` | TANPA ROLE — hitung global, semua role bisa lihat |
| W15 | GET | `/dashboard/pimpinan` | `DashboardController@pimpinan` | `dashboard.pimpinan` | TANPA ROLE — seharusnya pimpinan/auditor saja |
| W16 | GET | `/admin/pmb/verifikasi` | view `admin.pmb-verifikasi` (`Pmb\Verifikasi`) | `admin.pmb.verifikasi` | TANPA ROLE — admin-only by nama, tapi mahasiswa/dosen login bisa buka |
| W17 | GET | `/mahasiswa/krs` | view `mahasiswa.krs-pengajuan` (`Krs\Pengajuan`) | `mahasiswa.krs.pengajuan` | TANPA ROLE — perlu cek kepemilikan di komponen |
| W18 | GET | `/krs/{krs}/cetak` | `KrsController@cetak` | `krs.cetak` | **P0 — tanpa ownership check.** Hanya `$krs->load('details')`, user mana pun bisa cetak KRS siapa pun dengan menebak ID |
| W19 | GET | `/dosen/krs/persetujuan` | view `dosen.krs-persetujuan` (`Krs\PersetujuanPa`) | `dosen.krs.persetujuan` | TANPA ROLE |
| W20 | GET | `/lms/materi` | closure view `lms.materi` (`Lms\MateriCrud`, default `Pertemuan::latest('id')`) | `lms.materi` | TANPA ROLE; fallback IDOR (lihat §5 P1) |
| W21 | GET | `/lms/tugas` | closure view `lms.tugas` (default `Kelas::latest('id')`) | `lms.tugas` | TANPA ROLE; fallback IDOR |
| W22 | GET | `/lms/tugas/kumpul` | closure view `lms.kumpul` (default `Tugas::latest`, `Mahasiswa::latest` jika bukan mahasiswa) | `lms.tugas.kumpul` | **P0 IDOR** — non-mahasiswa dapat `Mahasiswa::latest('id')` lalu bertindak sebagai mahasiswa itu |
| W23 | GET | `/absensi/scan` | closure view `absensi.scan` (fallback `Mahasiswa::latest` sama) | `absensi.scan` | **P0 IDOR** — sama seperti W22 |
| W24 | GET | `/nilai/input` | view `nilai.input` (`Nilai\InputNilai`, default kelas terbaru) | `nilai.input` | TANPA ROLE — mahasiswa bisa buka halaman input nilai |
| W25 | GET | `/nilai/kunci` | view `nilai.kunci` (`Nilai\KunciNilai`) | `nilai.kunci` | TANPA ROLE — sama |
| W26 | GET | `/khs/{mahasiswaId}` | `KhsController@show` | `khs.show` | **P0 — tanpa ownership check.** Siapa pun login bisa lihat nilai siapa pun. IPK dihitung via `avg('bobot')` (bukan SKS-weighted) + `totalSks = 0` hardcode (data salah) |
| W27 | GET | `/transkrip/{mahasiswaId}/cetak` | `TranskripController@cetak` | `transkrip.cetak` | Perlu verifikasi — pola kemungkinan sama seperti W26 (belum diaudit isi file, tandai P0 sampai ada cek) |
| W28 | GET | `/skripsi/pengajuan` | livewire-page `skripsi.pengajuan-judul` | `skripsi.pengajuan` | TANPA ROLE (komponen pakai `Auth::id()->Mahasiswa`, lumayan, tapi dosen/admin bisa submit sebagai? cek P1) |
| W29 | GET | `/skripsi/pembimbing` | livewire-page `skripsi.set-pembimbing` | `skripsi.pembimbing` | TANPA ROLE |
| W30 | GET | `/bimbingan` | livewire-page `bimbingan.log` | `bimbingan.log` | TANPA ROLE |
| W31 | GET | `/sidang/pendaftaran` | livewire-page `sidang.pendaftaran` | `sidang.pendaftaran` | TANPA ROLE |
| W32 | GET | `/sidang/nilai` | livewire-page `sidang.nilai-sidang` | `sidang.nilai` | TANPA ROLE — mahasiswa bisa input nilai sidang sendiri? |
| W33 | GET | `/keuangan/tagihan` | livewire-page `keuangan.tagihan-massal` | `keuangan.tagihan` | TANPA ROLE — mahasiswa bisa generate tagihan massal |
| W34 | GET | `/keuangan/verifikasi` | livewire-page `keuangan.verifikasi-bayar` | `keuangan.verifikasi` | TANPA ROLE — ada `AuditLog` tapi tanpa cek role `keuangan` di komponen |
| W35 | GET | `/kwitansi/{pembayaran}/cetak` | `KwitansiController@cetak` | `kwitansi.cetak` | Perlu verifikasi ownership (tandai P0 sampai ada cek, pola seperti W26) |
| W36 | GET | `/keuangan/bayar` | `BayarController@index` | `keuangan.bayar` | OK parsial — scoped ke `Mahasiswa::where user_id`; tapi dosen/admin tanpa record mahasiswa dapat list kosong (bukan 403) — fungible |
| W37 | POST | `/keuangan/bayar/{tagihan}/va` | `BayarController@createVa` | `keuangan.bayar.va` | OK — ada `authorizeTagihan` (pemilik ATAU `keuangan`). Catatan: admin/dosen lain tetap 403 kecuali keuangan |
| W38 | GET | `/keuangan/bayar/status/{pembayaran}` | `BayarController@status` | `keuangan.bayar.status` | OK — via `authorizePembayaran` |
| W39 | GET | `/surat/pengajuan` | livewire-page `surat.pengajuan` | `surat.pengajuan` | TANPA ROLE |
| W40 | GET | `/surat/nomor` | livewire-page `surat.nomor-otomatis` | `surat.nomor` | TANPA ROLE — penomoran surat seharusnya admin/BAAK saja |
| W41 | GET | `/surat/{surat}/cetak` | `SuratController@cetak` | `surat.cetak` | Parsial — ada `abort_if(!nomor_surat, 403)` tapi **tanpa ownership check** (siapa pun bisa cetak surat siapa pun) |
| W42 | GET | `/media/generate` | livewire-page `media.generator` | `media.generate` | TANPA ROLE + biaya GPU — siapa pun bisa烧 GPU/ComfyUI |
| W43 | GET | `/notifikasi/broadcast` | livewire-page `notifikasi.broadcast` | `notifikasi.broadcast` | TANPA ROLE — mahasiswa bisa broadcast ke semua user |
| W44 | GET | `/pddikti` | `PddiktiController@index` | `pddikti.index` | TANPA ROLE — halaman feeder seharusnya admin/pimpinan |
| W45 | GET | `/pddikti/export/{jenis}` | `PddiktiController@export` | `pddikti.export` | TANPA ROLE — export data mahasiswa/nilai/kelulusan terbuka untuk semua login |
| W46 | POST | `/pddikti/push` | `PddiktiController@push` | `pddikti.push` | TANPA ROLE — siapa pun bisa push record ke Feeder dengan kredensial kampus |
| W47 | GET | `/admin/integrasi` | livewire-page `admin.integrasi` | `admin.integrasi` | **P1 — tanpa role.** Halaman baca/tulis semua kredensial (`pay.server_key/private_key`, FCM, PDDikti). `Setting::set` tanpa proteksi |

Rute mati/duplikat web: tidak ada duplikat path. Tidak ada route yang merujuk controller tidak ada (semua handler terdaftar ada). Rute yang berpotensi "mati" secara fungsional: `/dashboard` vs `/dashboard/pimpinan` (duplikasi logika statistik, bukan duplikat route).

### 3c. API v1 (`routes/api.php`, prefix `v1`)

| # | Method | URI | Handler | Middleware | Status |
|---|--------|-----|---------|------------|--------|
| A1 | POST | `/api/v1/login` | `Api\V1\AuthController@login` | `throttle:10,1` | OK; login via `nim_or_email` + password, ability `mobile` |
| A2 | GET | `/api/v1/me` | `AuthController@me` | `auth:sanctum` | OK |
| A3 | POST | `/api/v1/logout` | `AuthController@logout` | `auth:sanctum` | OK (hapus current token) |
| A4 | GET | `/api/v1/jadwal-hari-ini` | `JadwalController@hariIni` | `auth:sanctum` | TANPA ROLE — perlu cek isi: jadwal siapa? |
| A5 | GET | `/api/v1/krs-aktif` | `KrsController@aktif` | `auth:sanctum` | TANPA ROLE — perlu cek scoping mahasiswa |
| A6 | GET | `/api/v1/nilai-ringkas` | `NilaiController@ringkas` | `auth:sanctum` | TANPA ROLE — perlu cek scoping |
| A7 | POST | `/api/v1/absensi/scan` | `AbsensiController@scan` | `auth:sanctum` | TANPA ROLE — perlu cek token/window (lihat `AbsensiService::scan`) |
| A8 | GET | `/api/v1/tugas` | `TugasController@index` | `auth:sanctum` | TANPA ROLE |
| A9 | POST | `/api/v1/tugas/{id}/kumpul` | `TugasController@kumpul` | `auth:sanctum` | TANPA ROLE — cek kepemilikan mahasiswa |
| A10 | GET | `/api/v1/tagihan` | `TagihanController@index` | `auth:sanctum` | TANPA ROLE — cek scoping (kemungkinan aman mengikuti pola web) |
| A11 | POST | `/api/v1/tagihan/{tagihan}/va` | `TagihanController@createVa` | `auth:sanctum` | Perlu verifikasi ada authorize seperti web W37 atau tidak |
| A12 | GET | `/api/v1/bimbingan` | `BimbinganController@index` | `auth:sanctum` | TANPA ROLE |
| A13 | POST | `/api/v1/bimbingan` | `BimbinganController@store` | `auth:sanctum` | TANPA ROLE — siapa bisa tulis sebagai siapa? |
| A14 | GET | `/api/v1/pengumuman` | `PengumumanController@index` | `auth:sanctum` | OK (read, risiko rendah) |
| A15 | GET | `/api/v1/notifikasi` | `NotifikasiController@index` | `auth:sanctum` | Perlu cek scoping ke user sendiri |
| A16 | POST | `/api/v1/notifikasi/{notifikasi}/read` | `NotifikasiController@read` | `auth:sanctum` | **Wajib cek ownership** (tandai P0 sampai diverifikasi) |
| A17 | POST | `/api/v1/notifikasi/token` | `NotifikasiController@registerToken` | `auth:sanctum` | OK (register FCM token sendiri) |

Catatan API: tidak ada `role` middleware di level route sama sekali; `Sanctum ability mobile` dikeluarkan tapi **tidak pernah dicek** (`ability` middleware tidak dipakai). Throttle hanya di login. Tidak ada endpoint KHS/transkrip di API padahal PROGRESS mengklaim ada (`/khs/{tahun_id}` tidak ditemukan di route).

---

## 4. Inventaris Komponen

### 4a. Controller (22 file)

| File | Method | View/API | Dipakai oleh |
|------|--------|----------|--------------|
| `Controller.php` | base | — | semua controller |
| `SessionAuthController` | showLogin/login/logout | `auth.login` | W2, W3, W13 |
| `DashboardController` | index/pimpinan | `dashboard`, `dashboard.pimpinan` | W14, W15 |
| `PublikController` | prodi/prodiDetail/pengumuman/pengumumanDetail | `publik/*` (4 view) | W4–W7 |
| `PmbController` | create/store/cek | `pmb.create`, `pmb.cek` | W8–W10 |
| `KrsController` | cetak | `krs.cetak` | W18 |
| `KhsController` | show | `khs.show` | W26 |
| `TranskripController` | cetak | `transkrip.cetak` | W27 |
| `SuratController` | cetak/verify | `cetak.surat`, `cetak.surat-verify` | W41, W11 |
| `KwitansiController` | cetak | `cetak.kwitansi` | W35 |
| `BayarController` | index/createVa/callback/status | `keuangan.bayar` + JSON | W36, W37, W38, W12 |
| `PddiktiController` | index/export/push | `pddikti.index` + CSV/JSON | W44–W46 |
| `Api\V1\AuthController` | login/me/logout | JSON + `UserResource` | A1–A3 |
| `Api\V1\JadwalController` | hariIni | JSON | A4 |
| `Api\V1\KrsController` | aktif | JSON | A5 |
| `Api\V1\NilaiController` | ringkas | JSON | A6 |
| `Api\V1\AbsensiController` | scan | JSON | A7 |
| `Api\V1\TugasController` | index/kumpul | JSON | A8–A9 |
| `Api\V1\TagihanController` | index/createVa | JSON | A10–A11 |
| `Api\V1\BimbinganController` | index/store | JSON | A12–A13 |
| `Api\V1\PengumumanController` | index | JSON | A14 |
| `Api\V1\NotifikasiController` | index/read/registerToken | JSON | A15–A17 |

### 4b. Livewire (22 komponen) + view pemakai

| Komponen | View blade | Route pemakai |
|----------|-----------|---------------|
| `Pmb\DaftarPublik` | `livewire/pmb/daftar-publik` |嵌 di `pmb.create`? (perlu cek — tidak ada route langsung; kemungkinan dipakai di view PMB publik) |
| `Pmb\Verifikasi` | `livewire/pmb/verifikasi` | `admin.pmb.verifikasi` (W16) via `admin.pmb-verifikasi` |
| `Krs\Pengajuan` | `livewire/krs/pengajuan` | `mahasiswa.krs.pengajuan` (W17) via `mahasiswa.krs-pengajuan` |
| `Krs\PersetujuanPa` | `livewire/krs/persetujuan-pa` | `dosen.krs.persetujuan` (W19) via `dosen.krs-persetujuan` |
| `Lms\MateriCrud` | `livewire/lms/materi-crud` | `lms.materi` (W20) via `lms.materi` |
| `Lms\TugasCrud` | `livewire/lms/tugas-crud` | `lms.tugas` (W21) via `lms.tugas` |
| `Lms\KumpulTugas` | `livewire/lms/kumpul-tugas` | `lms.tugas.kumpul` (W22) via `lms.kumpul` |
| `Absensi\ScanQr` | `livewire/absensi/scan-qr` | `absensi.scan` (W23) via `absensi.scan` |
| `Nilai\InputNilai` | `livewire/nilai/input-nilai` | `nilai.input` (W24) via `nilai.input` |
| `Nilai\KunciNilai` | `livewire/nilai/kunci-nilai` | `nilai.kunci` (W25) via `nilai.kunci` |
| `Surat\Pengajuan` | `livewire/surat/pengajuan` | `surat.pengajuan` (W39) |
| `Surat\NomorOtomatis` | `livewire/surat/nomor-otomatis` | `surat.nomor` (W40) |
| `Skripsi\PengajuanJudul` | `livewire/skripsi/pengajuan-judul` | `skripsi.pengajuan` (W28) |
| `Skripsi\SetPembimbing` | `livewire/skripsi/set-pembimbing` | `skripsi.pembimbing` (W29) |
| `Sidang\Pendaftaran` | `livewire/sidang/pendaftaran` | `sidang.pendaftaran` (W31) |
| `Sidang\NilaiSidang` | `livewire/sidang/nilai-sidang` | `sidang.nilai` (W32) |
| `Bimbingan\Log` | `livewire/bimbingan/log` | `bimbingan.log` (W30) |
| `Keuangan\TagihanMassal` | `livewire/keuangan/tagihan-massal` | `keuangan.tagihan` (W33) |
| `Keuangan\VerifikasiBayar` | `livewire/keuangan/verifikasi-bayar` | `keuangan.verifikasi` (W34) |
| `Notifikasi\Broadcast` | `livewire/notifikasi/broadcast` | `notifikasi.broadcast` (W43) |
| `Media\Generator` | `livewire/media/generator` | `media.generate` (W42) |
| `Admin\Integrasi` | `livewire/admin/integrasi` | `admin.integrasi` (W47) |

### 4c. Service (12 file)

| Service | Tanggung jawab | Dipakai oleh |
|---------|---------------|--------------|
| `KeuanganService` | `hasTunggakan`, `isBlockedForKrs/Sidang`, `generateMassal` (chunk 200 + skip duplikat), `verifyPembayaran` (lockForUpdate + transaksi + update terbayar/status) | `Keuangan\TagihanMassal`, `Keuangan\VerifikasiBayar` |
| `AbsensiService` | `scan(pertemuan, mahasiswaId, token, now?)` | `Absensi\ScanQr`, `Api\V1\AbsensiController` (duplikasi logika scan web+API — cek konsistensi) |
| `SuratNomorService` | penomoran otomatis | `Surat\NomorOtomatis` |
| `NotifikasiService` | `broadcast(role, judul, isi)` via `User::role($role)->chunk(100)` + push FCM (`Setting fcm.*`) | `Notifikasi\Broadcast` |
| `Payment\PaymentGatewayInterface` | kontrak adapter | semua adapter + `PaymentService` |
| `Payment\PaymentService` | pilih driver dari `Setting pay.driver` / `config payment.default_driver`; `createVa`, `settleByReference`, `syncStatus`; validasi kredensial | `BayarController`, `Api\V1\TagihanController` |
| `Payment\SimulatedAdapter` | VA dummy dev | via `PaymentService` |
| `Payment\TripayAdapter` | Tripay VA/gerai/QRIS, HMAC callback | via `PaymentService` |
| `Payment\MidtransAdapter` | Midtrans Core API VA | via `PaymentService` |
| `Payment\XenditAdapter` | Xendit VA, callback token | via `PaymentService` |
| `PddiktiService` | `token/push/exportCsv` (chunk 500, CSV `;`) | `PddiktiController` |
| `ComfyUIService` | `generateImage(prompt, options)` (poll interval, timeout 600) | `Media\Generator`, `GenerateMediaJob` |

### 4d. Job, Command, Model, View lain

- **Job (1)**: `GenerateMediaJob(prompt, options, pengumumanId?)` → `ComfyUIService::generateImage` → update `pengumuman.cover_path`. Dipakai oleh `Media\Generator` dan/atau cover pengumuman.
- **Command (1)**: `Console\Commands\ComfyGenerateCommand` — CLI generate gambar. `routes/console.php` hanya berisi `inspire`; command mengandalkan auto-discovery.
- **Model (35)**: `User` (HasRoles + HasApiTokens), `Fakultas`, `Prodi`, `TahunAkademik`, `Mahasiswa`, `Dosen`, `Matakuliah`, `Kelas`, `Ruang`, `Krs`, `KrsDetail`, `Pertemuan`, `Materi`, `Tugas`, `TugasKumpul`, `Quiz`, `QuizSoal`, `QuizJawaban`, `Absensi`, `JurnalDosen`, `Nilai`, `Edom`, `PmbGelombang`, `PmbPendaftar`, `Tagihan`, `Pembayaran`, `SkripsiJudul`, `Bimbingan`, `Sidang`, `SidangPenguji`, `Repository`, `Surat`, `Pengumuman`, `Notifikasi`, `Setting`, `AuditLog`.
- **View non-Livewire (28)**: `welcome`, `auth.login`, `dashboard`, `dashboard.pimpinan`, `admin.pmb-verifikasi`, `mahasiswa.krs-pengajuan`, `dosen.krs-persetujuan`, `lms.{materi,tugas,kumpul}`, `absensi.scan`, `nilai.{input,kunci}`, `khs.show`, `transkrip.cetak`, `krs.cetak`, `cetak.{surat,surat-verify,kwitansi}`, `publik.{prodi-index,prodi-show,pengumuman-index,pengumuman-show}`, `pmb.{create,cek}`, `keuangan.bayar`, `pddikti.index`, `pages.livewire-page`, `layouts.tabler`.
- **Resource (1+)**: `Http\Resources\UserResource` (expose `roles`).
- **Middleware**: tidak ada middleware custom (`app/Http/Middleware` tidak ada). Hanya `auth`, `auth:sanctum`, `throttle` bawaan + CSRF-exception untuk `payment/callback`.

---

## 5. Temuan Tech-Debt & Risiko

### P0 — Kritis (perbaiki sebelum production)

1. **Tidak ada otorisasi peran di route web/API.** Seluruh grup `auth` (W13–W47) dan semua endpoint API (A4–A17) hanya `auth`/`auth:sanctum`. Spatie terpasang (`User::HasRoles`) tapi `role|permission` middleware tidak dipakai di route manapun. Dampak: mahasiswa bisa membuka verifikasi keuangan, broadcast, integrasi, PDDikti; dosen bisa verifikasi bayar; siapa pun bisa push ke Feeder. Rekomendasi: tambahkan `->middleware('role:...')` / `can:` per grup + policy (`KrsPolicy`, `NilaiPolicy`, `SuratPolicy`, `PembayaranPolicy`, dst) + sembunyikan menu sidebar per role.
2. **IDOR cetak & data akademik.** `KrsController@cetak` (W18), `KhsController@show` (W26) tanpa cek pemilik/peran; `SuratController@cetak` (W41) hanya cek `nomor_surat` terisi. Rute `W22/W23` fallback ke `Mahasiswa::latest('id')` untuk non-mahasiswa → bisa bertindak sebagai mahasiswa lain. Rekomendasi: scope ke `Auth::user()` + `hasRole` + 403, hapus fallback `latest('id')`.
3. **Halaman sensitif tanpa role: keuangan, PDDikti, integrasi, broadcast, media.** W33/W34/W40/W42–W47 terbuka untuk semua login. `admin.integrasi` menulis kredensial gateway/FCM/Feeder. `pddikti/push` bisa menyalahgunakan kredensial kampus. Rekomendasi: kunci ke `superadmin|keuangan|admin_prodi` sesuai fungsi + audit setiap tulis setting.
4. **Perhitungan KHS salah + SKS nol.** `KhsController@show:14` — `$ipk = avg('bobot')` (harusnya SKS-weighted), `$totalSks = 0` hardcode. Rekomendasi: join `kelas->matakuliah.sks`, hitung `sum(bobot*sks)/sum(sks)`.
5. **Callback payment & Simulated di production.** `bootstrap/app.php:18` CSRF-exception benar, tapi keamanan bergantung penuh pada `verifyCallback` tiap adapter. Pastikan `SimulatedAdapter::verifyCallback` tidak bisa dipicu di prod (matikan driver `simulated` via validasi `PAY_DRIVER` + kunci route callback hanya dari IP gateway bila perlu).
6. **Login web tanpa throttle; PMB publik tanpa anti-spam.** `POST /login` (W3) tanpa `throttle`; `POST /pmb` (W9) tanpa captcha/honeypot/throttle → brute force & spam pendaftar. Rekomendasi: `throttle:5,1` untuk login web, throttle + honeypot + validasi `prodi1_id/prodi2_id exists:prodi,id` untuk PMB (saat ini hanya `min:1`, bisa FK invalid).

### P1 — Penting (perbaiki sprint berikutnya)

7. **API ability `mobile` tidak dicek; scoping per-endpoint belum terverifikasi.** `createToken('mobile', ['mobile'])` tapi tidak ada `abilities:mobil` middleware. A4–A17 perlu audit satu per satu (Jadwal/KRS/Nilai/Tugas/Tagihan/Bimbingan/Notifikasi) untuk scoping `Auth::user()->mahasiswa/dosen`.
8. **Sidebar tidak sadar peran** (`tabler.blade.php:50-64`) — semua menu tampil untuk semua user. Bukan sekadar UX: mengiklankan endpoint sensitif. Rekomendasi: bungkus dengan `@role`/`@hasrole` (Spatie Blade directives).
9. **Duplikasi logika & inkonsistensi.**
   - Statistik dashboard dihitung di 3 tempat: closure `/` (W1), `DashboardController@index`, `DashboardController@pimpinan` — gabung ke satu service/view-composer + cache.
   - Scan absensi ganda: `Absensi\ScanQr` + `Api\V1\AbsensiController` → pastikan keduanya memanggil `AbsensiService::scan` yang sama (sudah untuk web; verifikasi API).
   - `createVa` ganda: `BayarController` + `Api\V1\TagihanController` → pindahkan otorisasi ke `PaymentService`/policy bersama.
   - `NotifikasiService::broadcast` dipakai Livewire; API mobile hanya read — tidak ada duplikat tulis, OK.
10. **Validasi PMB longgar.** `prodi1_id/prodi2_id` hanya `min:1` tanpa `exists:prodi,id`; `sekolah_asal` bebas; `berkas_json`/upload tidak terlihat di `store` (mungkin di Livewire `DaftarPublik` — perlu pastikan alur ganda form publik vs Livewire tidak divergen). Rekomendasi: `exists` + normalisasi + satu pintu (controller ATAU Livewire, bukan dua).
11. **Model tanpa UI/API (dead atau belum selesai): `Quiz/QuizSoal/QuizJawaban`, `Edom`, `JurnalDosen`, `Repository`.** Ada migrasi + model + factory/seeder (`DemoSeeder` seed Quiz & JurnalDosen) tapi tidak ada route/controller/Livewire yang memakainya (grep hanya kena model/factory/seeder + `Sidang\Pendaftaran` pakai `Ruang`). Klaim PROGRESS "LMS+Absensi + QR + EDOM" dan "Skripsi + repository" tidak tercermin di route. Rekomendasi: putuskan — bangun UI-nya (quiz CRUD, EDOM kuesioner, jurnal dosen, repository upload) atau tandai eksplisit out-of-scope + hapus seeder agar tidak mengecoh.
12. **`mobile/` Flutter tidak ada di repo** padahal PRD M11 & PROGRESS P8 mengklaim "Flutter stub". Tidak ada folder `mobile/`. Rekomendasi: buat stub atau koreksi dokumen.
13. **Livewire tanpa guard peran; kepercayaan pada `Auth::id()` saja.** Hampir semua `mount`/action hanya `Auth::id()` → `Mahasiswa::where user_id`. Dosen/admin yang tidak punya record mahasiswa dapat `null`/`firstOrFail` (500) atau fallback salah (W22/W23). Rekomendasi: `mount` cek role + `authorize` + pesan 403 yang ramah.
14. **AuditLog hanya manual & parsial.** Ditulis di 9 Livewire (keuangan, surat, skripsi, sidang, bimbingan) tapi tidak di: login/logout, input/kunci nilai, KRS persetujuan, export/push PDDikti, ubah setting integrasi, callback payment. Rekomendasi: observer/global trait `Auditable` + sertakan `diff` untuk nilai/keuangan/sidang.

### P2 — Nice-to-have / hygiene

15. **Tidak ada TODO/FIXME di `app/`** (grep kosong) — bagus, tapi berarti tech-debt tidak terdokumentasi di kode. Tambahkan `@todo` untuk item P0/P1 di atas agar terlacak.
16. **N+1 & query di view/closure.** Closure route `/` + `lms.*` + `absensi.scan` memanggil `Model::latest('id')->value('id')` tiap request; `PddiktiService::rowsNilai` akses `$n->kelas->matakuliah` dalam chunk (sudah `with('mahasiswa','kelas.matakuliah')` — OK). Rekomendasi: eager-load + cache untuk default ID + `chunkById`.
17. **CSV PDDikti delimiter `;` + nama file timestamp** — OK untuk Excel ID, tapi pastikan import Feeder menerima `;` dan direktori `exports` dibersihkan terjadwal (tambah command prune + schedule).
18. **ComfyUI timeout 600 dtk + poll 3 dtk di request Livewire** — risiko timeout HTTP/worker. Sudah ada `GenerateMediaJob` (queue) — pastikan `Media\Generator` selalu dispatch job, bukan panggil `ComfyUIService` sinkron. Tambahkan retry/backoff + hapus file workflow gagal.
19. **`Setting::get` memakai `Cache::rememberForever` tanpa TTL** — OK karena `set` forgets, tapi tidak ada namespace per-env; `config:cache` + `settings` bisa basi antar env. Tambahkan prefix env + perintah `settings:clear`.
20. **Dokumen vs realita.** `PROGRESS.md` mengklaim "SEMUA SELESAI, 46 test passed" dan menyebut Livewire 3 padahal `composer.json` memakai Livewire 4.4; PRD menyebut Breeze/Fortify + Redis/Horizon/Meilisearch/S3 yang tidak ada di `composer.json` (tanpa `predis`, `horizon`, `meilisearch`, `flysystem-s3`). Rekomendasi: selaraskan versi di dokumen + putuskan dependensi yang benar-benar dibutuhkan.
21. **Tidak ada middleware/files Rate-limit global API, tidak ada `docs/TESTING.md`** (dirujuk PROGRESS DoD tapi file tidak ada — hanya `PROGRESS/PRD/INTEGRASI/COMFYUI`). Tambahkan matriks uji + `php artisan test` di CI.
22. **Duplikat migrasi sidang/skripsi** (`..._000006_create_skripsi_juduls` dan `..._000006_create_nilai_edom` berbagi prefix nomor) — bukan error (nama file unik), tapi rawan urutan; pertimbangkan penomoran ulang/squash untuk instalasi baru.

---

## 6. Dependensi Antar Modul

```
PMB -> Mahasiswa/User (daftar ulang auto-create) -> Tagihan awal (keuangan)
KRS -> Keuangan (isBlockedForKrs via tunggakan) + Akademik (SKS/IPK di Krs model) + Dosen PA (PersetujuanPa)
LMS/Absensi -> Kelas/Pertemuan -> Nilai (kehadiran) ; AbsensiService dipakai web + API
Nilai -> KHS/Transkrip (KhsController) -> PDDikti export (rowsNilai) ; KunciNilai mengunci revisi
Skripsi -> Bimbingan -> Sidang (syarat: SKS lulus, TOEFL?, bebas tunggakan via KeuanganService::isBlockedForSidang) -> Repository (klaim, belum ada link kode)
Surat -> SuratNomorService + verifikasi QR publik (tanpa auth)
Keuangan -> PaymentService/adapters + Setting + AuditLog + Kwitansi cetak
Notifikasi -> User::role + Setting FCM + Notifikasi model -> API mobile read
Media (ComfyUI) -> GenerateMediaJob -> Pengumuman.cover_path (dependensi silang LMS-publik ke GPU eksternal)
PDDikti -> Mahasiswa/Nilai + Setting kredensial + halaman Integrasi (dependensi admin tunggal)
Settings (Admin\Integrasi) -> SEMUA integrasi (pay, fcm, pddikti, comfy) — single point of failure jika disalahgunakan (lihat P0-3)
```

Siklus kritis yang sudah tersambung kode: PMB→Mahasiswa→KRS (block tunggakan)→LMS/Absensi→Nilai→KHS→Sidang (block tunggakan)→Keuangan. Yang belum tersambung: Quiz/EDOM/Jurnal/Repository (model saja).

---

## 7. Rekomendasi Prioritas

| Prioritas | Aksi | File terdampak |
|-----------|------|----------------|
| P0 | Tambah `role`/`can` middleware + Policy untuk W15–W47, A4–A17; sembunyikan menu per role | `routes/web.php`, `routes/api.php`, `tabler.blade.php`, `app/Policies/*` (baru) |
| P0 | Perbaiki IDOR W18/W22/W23/W26/W27/W35/W41 + hapus fallback `latest('id')` | `KrsController`, `KhsController`, `TranskripController`, `KwitansiController`, `SuratController`, `web.php` closure |
| P0 | Perbaiki rumus IPK + `totalSks` | `KhsController@show`, `khs.show`, `TranskripController` |
| P0 | Throttle login web + PMB; validasi `exists:prodi,id`; matikan `simulated` di prod | `web.php`, `PmbController`, `PaymentService`, `.env` |
| P1 | Audit scoping API satu per satu + cek ability `mobile` | `Api\V1\*` |
| P1 | Bangun atau buang Quiz/EDOM/Jurnal/Repository; stub `mobile/` atau koreksi docs | Livewire baru / hapus seeder, `docs/*` |
| P1 | AuditLog global (observer) untuk nilai/keuangan/PDDikti/setting/auth | `AuditLog`, observer, `Admin\Integrasi` |
| P2 | Service statistik dashboard + cache; prune `exports/`; queue ComfyUI selalu async | `DashboardController`, `PddiktiService`, `Media\Generator` |
| P2 | Selaraskan `docs/*` (versi Livewire, dependensi Redis/Horizon/S3/Meilisearch, TESTING.md) | `docs/PROGRESS.md`, `docs/PRD.md` |

---

## 8. Lampiran — Cara Verifikasi Ulang (read-only)

```powershell
# struktur
Get-ChildItem app/Http/Controllers, app/Livewire, app/Services, app/Jobs, app/Console -Recurse -Filter *.php | Select FullName
php artisan route:list --method=GET --except-vendor
php artisan route:list --path=api --except-vendor
Select-String -Path app -Pattern 'TODO|FIXME|hasRole|abort_unless|AuditLog::create' -Recurse
```

*Audit ini tidak mengubah kode; satu-satunya file yang ditulis adalah dokumen ini (`docs/AUDIT_ARCH.md`).*
