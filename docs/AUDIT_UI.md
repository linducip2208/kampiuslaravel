# Audit UI/UX — SIMAK Kuliah vs 4 Mockup Referensi

> Workspace: `resources/views` · Layout: `layouts/tabler.blade.php` (Tabler via CDN, wajib 100% Tabler)
> Mockup: (1) landing dark-navy UCN · (2) landing terang "AI ERP for Education" · (3) dashboard admin putih "Acadex" · (4) portal mahasiswa dark-blue/oranye SIAMUB
> Tanggal audit: 2026-11-12 · Sifat: READ-ONLY, tanpa perubahan kode selain dokumen ini.

## Ringkasan skor (0–10)

| Area | Skor | Kesimpulan |
|---|---|---|
| Landing `welcome.blade.php` vs mockup 1+2 | 7 | Struktur (hero dark + stat + lifecycle + role + CTA) sudah sejajar mockup 1. Kurang: preview dashboard statistik+chart mockup 2, testimoni/logo PT, SEO/meta, CTA ganda lemah. |
| Login `auth/login.blade.php` vs mockup 4 (login split) | 5 | Fungsional dan Tabler `page-center+card-md`, tapi masih satu kolom polos — belum split ilustrasi/branding SIAMUB, belum show-password, belum SSO/divider. |
| Dashboard umum `dashboard.blade.php` vs mockup 3 (Acadex) | 4 | Hanya 4 stat cards + 6 tombol aksi. Mockup 3 menuntut: line/bar chart, kalender, schedule, messages, donut, activity, notice board. Nol chart/kalender di repo. |
| Dashboard pimpinan `dashboard/pimpinan.blade.php` vs mockup 3 | 3 | 10 stat cards teks saja, tanpa chart/grafik, tanpa filter periode, tanpa tabel top-prodi/risiko DO. |
| Wrapper halaman (lms/*, nilai/*, absensi/*, pmb/*, cetak/*, khs/*, krs/*, transkrip/*, keuangan/bayar, pddikti/*, publik/*) | 7 | Konsisten: `@extends('layouts.tabler')` + `pretitle/page-title` + `card/table-responsive/dl.row`. Cetak sudah `d-print-none`. Masalah kecil (lihat tabel). |
| Komponen Livewire `livewire/*` (16 file) | 2 | **Temuan terbesar.** Mayoritas HTML mentah tanpa class Tabler: `<table>` polos, `<input>/<select>/<textarea>/<button>` tanpa `form-control/form-select/btn`, pesan tanpa `alert`, list pakai `<ul><li>`. Hanya 3 file yang sudah Tabler: `notifikasi/broadcast`, `admin/integrasi`, `media/generator`. |
| Konsistensi Tabler global | 5 | Layout + wrapper + publik + cetak = Tabler. Livewire = tidak. 1 inline `style=` (daftar-publik:3). Tidak ada CSS framework lain (tidak ada class Tailwind/Bootstrap non-Tabler terdeteksi). |
| Navigasi / aksi mati | 6 | Sidebar `layouts/tabler.blade.php:52-63` memakai satu route per item sehingga label bisa menyesatkan (mis. `PMB→admin.pmb.verifikasi`, `LMS→lms.materi`, `Nilai→nilai.input` untuk semua role). Tidak ada breadcrumb. Pagination Livewire (`$x->links()`) belum dibungkus Tabler. |
| Empty / loading / error states | 5 | Empty state ada tapi minimal (`alert-info` "Belum ada…"). Nol `wire:loading`/spinner/skeleton di seluruh `livewire/*` (grep `wire:loading` = 0 hasil). Error inline `@error` tanpa class `invalid-feedback`/`is-invalid`. |
| Responsif | 6 | Wrapper memakai `row/col-sm-6/col-lg-3/col-md-*/table-responsive` dengan benar. Livewire table polos tanpa wrapper `table-responsive` → overflow di mobile. Form Livewire tanpa grid (`col-md-*`) → satu kolom sempit di desktop, menumpuk acak di mobile. |
| Aksesibilitas | 5 | Layout punya `aria-label`/`aria-controls` pada toggler dan dropdown akun. Banyak input Livewire tanpa `<label for>` atau `aria-describedby`; tombol aksi hanya teks ("Detail/Verifikasi/Lulus…") tanpa `aria-label`; kontras `text-white-50` di atas `bg-dark` tipis; `img cover` sudah punya `alt` (publik/*, generator) — baik. |

## Kesesuaian per mockup

### Mockup 1 — Landing dark-navy (hero + mockup dashboard + lifecycle + role cards + CTA)
- Sudah: `welcome.blade.php:9-72` hero `bg-dark` + badge mockup + 3 mini stat cards; `:87-127` lifecycle 3 kartu (PMB→Mhs, Akademik→Transkrip, Keuangan→Laporan); `:130-180` 4 role cards (Rektor/BAAK/Finance/Mahasiswa); `:183-195` CTA band.
- Kurang: mockup menampilkan screenshot dashboard nyata di hero — saat ini hanya 3 angka statis; tidak ada section logo/testimoni/partner; checklist "✓ UUID-ready…" (`:23-28`) memakai karakter teks, ganti `ti-icon`/Tabler icons; footer `:197-204` terlalu minimal (tanpa kolom link/bantuan/kontak).

### Mockup 2 — Landing terang "AI ERP for Education" (preview statistik + chart)
- Belum dipenuhi: tidak ada varian terang, tidak ada panel preview statistik + chart (line/bar) di landing. Rekomendasi: tambah section `card` terang berisi 4 KPI + placeholder chart ApexCharts/Tabler (boleh CDN chart, tetap 100% Tabler untuk layout) atau gunakan kembali mini-stat pimpinan.

### Mockup 3 — Dashboard admin putih "Acadex"
- Gap terbesar. `dashboard.blade.php:8-42` dan `dashboard/pimpinan.blade.php:8-33` hanya stat `card` + tombol.
- Wajib tambah (semua tersedia di Tabler): `row-deck` berisi (a) line chart kehadiran/pembayaran, (b) bar chart KRS per prodi, (c) `card` kalender + jadwal sidang hari ini, (d) donut komposisi status mahasiswa/pembayaran, (e) `card` aktivitas terbaru (audit trail sudah ada di DB), (f) notice board (pengumuman terbaru). Semua via `card-header/card-body/datagrid`.

### Mockup 4 — Portal mahasiswa dark-blue/oranye SIAMUB (login split, KRS table, biodata, presensi, IPK chart)
- Login (`auth/login.blade.php:1-45`) standalone, tidak extend layout — boleh, tapi belum split: tambah `row` 2 kolom (kiri branding `bg-primary` + kanan form), tombol `btn-orange` Tabler untuk aksen oranye SIAMUB, link "Lupa password / bantuan".
- KRS mahasiswa (`livewire/krs/pengajuan.blade.php:1-45`): tabel polos, tampil `kelas_id`/kode mentah — mockup menuntut tabel KRS dengan nama MK, jadwal, dosen, SKS + total SKS + status badge. Perlu join nama di komponen, bukan sekadar `kelas->nama ?? Kelas #id`.
- Biodata/presensi/IPK chart: belum ada halaman biodata mahasiswa, presensi hanya input token (`livewire/absensi/scan-qr.blade.php:1-9`), KHS (`khs/show.blade.php:18-31`) tampil `kelas_id` mentah tanpa nama MK — harus tampil nama MK + SKS + grafik IPK sparkline.

## Daftar halaman + masalah konkret (file:baris)

### Layout & global
- `layouts/tabler.blade.php:19-21` — Brand hanya teks "SIMAK Kuliah", tanpa logo/avatar instansi; mockup 1/4 selalu menampilkan logo.
- `layouts/tabler.blade.php:52-63` — Satu route per menu untuk semua role (`PMB→admin.pmb.verifikasi`, `LMS→lms.materi`, `Nilai→nilai.input`, `Keuangan→keuangan.tagihan`, `Skripsi→skripsi.pengajuan`, `Surat→surat.pengajuan`, `Laporan→dashboard.pimpinan`). Mahasiswa/dosen yang klik bisa 403/redirect. Perlu gate per-role atau submenu.
- `layouts/tabler.blade.php:49,66-68` — `route($item['route'])` dievaluasi untuk semua item meski user tak punya akses; bila route hilang → 500 seluruh layout. Bungkus dengan `Route::has()` / `@can`.
- `layouts/tabler.blade.php:90` — Dropdown akun `href="#"` (navigasi mati). Ganti `<button>` + tetap `data-bs-toggle="dropdown"`.
- `layouts/tabler.blade.php:111-127` — `page-header` tanpa breadcrumb. Tambah `ol.breadcrumb` Tabler (Beranda / Modul / Halaman).
- `layouts/tabler.blade.php:128-148` — Hanya `session('success')` + `$errors`; tidak ada slot `session('info/warning/error')`. Livewire memakai `session('info')`/`$message` polos sehingga tak tampil konsisten.

### Landing
- `welcome.blade.php:20-21` — Dua CTA ("Coba demo" + "Lihat dokumentasi") mengarah ke `login` dan `publik.prodi` (bukan demo/dokumentasi). Label menyesatkan.
- `welcome.blade.php:23-28` — Checklist memakai karakter `✓` teks; ganti ikon Tabler (`ti ti-check`) dalam `list-unstyled`.
- `welcome.blade.php:33` — `badge-dot` dipakai di dalam `badge` (`<span class="badge badge-dot …">`) — bukan pola Tabler (`status-dot`/`badge-dot` berdiri sendiri). Samakan dengan `:14` (`status-dot status-dot-animated`).
- `welcome.blade.php:41-65` — Mini stat cards di hero memakai `col-4` tetap → sempit di mobile. Pakai `col-12 col-sm-4`.
- `welcome.blade.php:77-83` — Stats strip `col-3` tetap → 4 kolom terjepit di HP. Pakai `col-6 col-md-3`.
- `welcome.blade.php:78,80` — Angka `display-6` tanpa label tren/sparkline seperti mockup 2.

### Auth
- `auth/login.blade.php:12` — `h1` teks polos tanpa logo; tidak ada `page-pretitle`/breadcrumb (wajar untuk auth, tapi mockup 4 butuh panel kiri branding).
- `auth/login.blade.php:17` — Error hanya `alert-danger` teks pertama; tanpa `role="alert"` dan tanpa `is-invalid` pada input.
- `auth/login.blade.php:22-27` — `label.form-label` tanpa `for` yang terhubung (`email`/`password` tidak punya `id`). Tambah `id` + `for`.
- `auth/login.blade.php:36-38` — Kredensial demo hardcoded di view produksi. Pindahkan ke `@env('local')` / hapus di prod.
- `auth/login.blade.php:9` — `body.d-flex.flex-column` tanpa `border-top-wide border-primary` khas Tabler `page-center`; tambah untuk aksen.
- Tidak ada "tampilkan password", "lupa password", caps-lock warning, maupun `wire:loading`/disabled saat submit.

### Dashboard
- `dashboard.blade.php:11-14` — Kartu mengarah ke route campur-role (`mahasiswa.krs.pengajuan`, `dosen.krs.persetujuan`, `publik.prodi`, `lms.materi`) tanpa otorisasi tampilan; tombol "Buka" generik.
- `dashboard.blade.php:22` — Angka `h1` tanpa `datagrid`/tren; tidak ada chart/kalender/schedule/messages/donut/activity/notice (mockup 3).
- `dashboard.blade.php:32-39` — 6 tombol `btn-outline-primary` semua identik; prioritas visual hilang. Bedakan primer vs sekunder; tambah ikon.
- `dashboard/pimpinan.blade.php:11-20` — 10 kartu tanpa tautan/detail (angka mati); `Belum Bayar` + `Pending` seharusnya `danger/warning` + link ke verifikasi.
- `dashboard/pimpinan.blade.php:28` — `h1 mb-0` tanpa subheader tren; tidak ada filter periode/tahun akademik.

### Wrapper bermasalah ringan
- `lms/materi.blade.php:15`, `lms/tugas.blade.php:15`, `lms/kumpul.blade.php:15`, `nilai/input.blade.php:15`, `nilai/kunci.blade.php:15` — Empty state hanya `alert-info` + instruksi query-string `?pertemuan=ID`/`?kelas=ID` (bukan pemilih). Ganti dengan `empty` Tabler + tombol "Buat/Pilih" + `select`.
- `absensi/scan.blade.php:15` — Empty "Belum ada data mahasiswa" tanpa CTA tambah/cari mahasiswa.
- `pmb/cek.blade.php:14` — `label for="no_daftar"` sudah benar; tapi hasil `dl.row` tanpa `card-status-top`; status selain `lulus` selalu abu-abu (`:30`) — bedakan `diverifikasi/ditolak`.
- `keuangan/bayar.blade.php:17` — Status teks polos (`{{ $t->status }}`); samakan badge seperti `:20` cetak kwitansi.
- `keuangan/bayar.blade.php:56-71` — `fetch` manual + `alert()` + `location.reload()`; tanpa `wire:loading`/disabled, tanpa `role="status"`, `onclick` inline (tetap boleh, tapi pindah ke `@push('scripts')` listener atau Livewire action agar CSP-aman).
- `keuangan/bayar.blade.php:31` — `div#va-result` kosong tanpa `aria-live="polite"`.
- `khs/show.blade.php:13` — Judul "Mahasiswa #id" (ID mentah, mockup 4 butuh NIM+nama+foto/biodata ringkas).
- `khs/show.blade.php:24-26`, `krs/cetak.blade.php:28`, `transkrip/cetak.blade.php:20` — Kolom menampilkan `kelas_id` mentah, tanpa nama MK/SKS/dosen. Perlu eager-load relasi.
- `krs/cetak.blade.php:15` — Status selain `disetujui` selalu abu-abu; bedakan `diajukan/ditolak`.
- `cetak/surat.blade.php:16`, `cetak/kwitansi.blade.php:22` — `{{ $verified_at ?? '-' }}` / URL verifikasi mentah; tampilkan QR/badge + tanggal format lokal.
- `publik/prodi-show.blade.php:38-40`, `publik/pengumuman-index.blade.php:13-15`, `publik/pengumuman-show.blade.php:10-12` — `img.card-img-top` sudah benar + `alt` ada (bagus); tapi tanpa `ratio`/fallback bila gambar rusak; `pengumuman-show` tanpa tombol kembali/breadcrumb.
- `publik/pengumuman-index.blade.php:28` — `{{ $pengumuman->links() }}` tanpa pembungkus `d-flex justify-content-center mt-3`.
- `pddikti/index.blade.php:27` — Info Feeder hanya `alert-info`; tambah `card` langkah + status koneksi terakhir.

### Livewire — pelanggaran Tabler (prioritas perbaikan tertinggi)
Pola umum: `livewire/*` tidak memakai `form-control/form-select/form-check-input/btn/card/table/table-vcenter/alert`. Rincian:

- `livewire/krs/pengajuan.blade.php:2-4,6-8` — `session('info')`/`$pesanBlokir` dalam `<div>` polos → ganti `alert alert-info/warning` + `role="alert"`.
- `livewire/krs/pengajuan.blade.php:11-12` — `label+input` tanpa `form-label`/`form-control`; tanpa `mb-3`, `hint`, `invalid-feedback`.
- `livewire/krs/pengajuan.blade.php:18,30,36,43` — `<table>` polos, checkbox tanpa `form-check-input`, tombol submit tanpa `btn btn-primary`. Bungkus `card > table-responsive > table.table.table-vcenter`; tombol `btn btn-primary`; tambah `wire:loading.attr="disabled"` + `span.spinner-border.spinner-border-sm`.
- `livewire/krs/persetujuan-pa.blade.php:2-4,7,12,16,34-35,44` — Sama: info polos, search tanpa `form-control` + ikon, tabel polos tanpa `table-responsive`, tombol tanpa `btn btn-success/btn-danger btn-sm`, pagination tanpa wrapper. Status KRS tidak ditampilkan sebagai `badge`.
- `livewire/pmb/verifikasi.blade.php:2-4,7-8,16,34-38,50-59` — Search/filter tanpa `form-control/form-select` + tanpa `row g-2`; tabel polos; 5 tombol aksi tanpa class/warna; panel detail tanpa `card`; `textarea` tanpa `form-control`.
- `livewire/pmb/daftar-publik.blade.php:3` — **Satu-satunya `style=` inline di views** (`border:1px solid #2fb344…`) → ganti `alert alert-success`.
- `livewire/pmb/daftar-publik.blade.php:10-59` — Seluruh form tanpa `form-label/form-control/form-select/mb-3/row`; tombol tanpa `btn`; error `<span>` polos → `invalid-feedback` + `is-invalid`; `prodi1_id/prodi2_id` berupa input angka ID mentah (UX buruk, mockup PMB butuh dropdown nama prodi).
- `livewire/absensi/scan-qr.blade.php:1-9` — Form satu baris tanpa `form-control/btn`; pesan `<p>` polos → `alert`; tanpa label, tanpa `wire:loading`, tanpa riwayat absensi/presensi (mockup 4 butuh tabel presensi + persentase).
- `livewire/nilai/input-nilai.blade.php:1-14` — Baris nilai berupa `<div><span>+4 input>` tanpa grid/table/label; tanpa `form-control form-control-sm`, tanpa nama mahasiswa (hanya `Mhs #id`), tanpa validasi visual, tanpa total/rata-rata.
- `livewire/nilai/kunci-nilai.blade.php:1-5` — Hanya `<p>` + 2 tombol tanpa class → `card-body` + `badge` + `btn btn-warning/btn-outline-danger` + konfirmasi.
- `livewire/lms/materi-crud.blade.php:1-17`, `livewire/lms/tugas-crud.blade.php:1-17` — Form tanpa `form-*`, daftar memakai `<ul><li>` + tombol tanpa class → `card` + `table` + `btn btn-sm btn-outline-*`; `tugas-crud:16` pagination tanpa wrapper.
- `livewire/lms/kumpul-tugas.blade.php:1-11` — `h3` judul tanpa `card-title`, info file path mentah (`file_path` diekspos), tanpa `alert`, tanpa `form-control`, tanpa status dinilai/belum.
- `livewire/keuangan/verifikasi-bayar.blade.php:1-18` — Pesan `<p>`, `select` tanpa `form-select`, daftar `<ul><li>` + tombol tanpa class → `table.table` + `badge` status + `btn-sm`.
- `livewire/keuangan/tagihan-massal.blade.php:1-26` — Form tanpa `form-*/mb-3/row/col`, tombol tanpa `btn` → `card-body row g-2` + `btn btn-primary`; tanpa ringkasan hasil generate.
- `livewire/skripsi/pengajuan-judul.blade.php:1-14`, `livewire/skripsi/set-pembimbing.blade.php:1-37`, `livewire/sidang/pendaftaran.blade.php:1-31`, `livewire/sidang/nilai-sidang.blade.php:1-30`, `livewire/surat/pengajuan.blade.php:1-18`, `livewire/surat/nomor-otomatis.blade.php:1-20`, `livewire/bimbingan/log.blade.php:1-23` — Pola identik: `<ul>/<table>` polos, input/select/textarea/button tanpa class Tabler, pesan `<p>` polos, riwayat tanpa `badge` status, tanpa `empty` state, tanpa `wire:loading`.
- Pengecualian baik (jadikan contoh): `livewire/notifikasi/broadcast.blade.php:5-31`, `livewire/admin/integrasi.blade.php:6-69`, `livewire/media/generator.blade.php:6-45` — sudah `card/card-header/card-body/form-label/form-control/form-select/btn/row/col/alert/row-cards`. Namun `integrasi` dan `generator` menyimpan `password` via `wire:model` teks biasa — tambah `autocomplete="new-password"` dan masker.

## Rekomendasi perbaikan prioritas (100% Tabler, tanpa framework lain)

### P0 — Wajib (merusak UX/fungsi)
1. **Tablerisasi 13 file Livewire mentah.** Template baku per komponen:
   `div > alert(role=alert) > form.card > card-body > row g-2 > col-md-* > mb-3 > label.form-label(for+id) > input.form-control / select.form-select / textarea.form-control + div.invalid-feedback > table-responsive > table.table.table-vcenter > thead > badge status > btn btn-primary/btn-outline-* btn-sm + wire:loading (spinner-border spinner-border-sm + disabled).`
   Daftar: `krs/pengajuan`, `krs/persetujuan-pa`, `pmb/verifikasi`, `pmb/daftar-publik`, `absensi/scan-qr`, `nilai/input-nilai`, `nilai/kunci-nilai`, `lms/materi-crud`, `lms/tugas-crud`, `lms/kumpul-tugas`, `keuangan/verifikasi-bayar`, `keuangan/tagihan-massal`, `skripsi/* (2)`, `sidang/* (2)`, `surat/* (2)`, `bimbingan/log`.
2. **Ganti ID mentah dengan nama relasi** di KRS/KHS/transkrip/nilai (`kelas->matakuliah->nama`, `mahasiswa->nim/nama`, `dosen->nama`) + tambah kolom Jadwal/Dosen/SKS dan `badge` status. Tanpa ini mockup 4 (tabel KRS) tidak tercapai.
3. **Perbaiki navigasi role.** Ubah `$menu` di `layouts/tabler.blade.php:51-64` menjadi per-role (`@can`/`@role`) atau pisah sidebar admin/dosen/mahasiswa; bungkus `route()` dengan `Route::has()`; ubah dropdown akun `a[href=#]` menjadi `button`.
4. **Tambah loading + konfirmasi pada semua aksi Livewire.** `wire:loading`, `wire:loading.attr="disabled"`, `wire:confirm="Yakin?"` untuk hapus/tolak/kunci, dan `alert` sukses/gagal yang konsisten (ganti `<p>{{ $message }}` polos).

### P1 — Penting (mockup parity)
5. **Dashboard Acadex (mockup 3).** Di `dashboard.blade.php` + `dashboard/pimpinan.blade.php` tambah `row-deck`: chart garis kehadiran & pembayaran, bar KRS per prodi, donut status bayar/KRS, `card` kalender mini + "Jadwal hari ini" (sidang/kelas), "Aktivitas terbaru" (dari audit), "Notice board" (pengumuman terbaru). Tetap Tabler (`card`, `datagrid`, `badge`, `avatar`, `progress`).
6. **Login split SIAMUB (mockup 4).** `auth/login.blade.php` → `container-fluid` 2 kolom: kiri `bg-primary text-white` (logo, tagline, 3 keunggulan, ilustrasi Tabler), kanan `card-md` form + `input-group` show-password + `form-check` remember + divider "atau" + tombol demo per-role. Hapus kredensial hardcoded dari view produksi.
7. **Landing terang + preview (mockup 2).** Tambah section terang di `welcome.blade.php`: 4 KPI + tabel/chart mini + logo PT/testimoni + FAQ + footer multi-kolom. Perbaiki `col-4/col-3` menjadi responsif (`col-12 col-sm-4`, `col-6 col-md-3`).
8. **Biodata + presensi + IPK chart (mockup 4).** Buat/rapikan halaman mahasiswa: header `card` biodata (avatar, NIM, prodi, PA, status `badge`), tabel presensi per-MK dengan `progress` %, sparkline IPK per semester. `khs/show` dan `transkrip/cetak` wajib tampil nama MK bukan `kelas_id`.

### P2 — Polish
9. **Breadcrumb + page-actions konsisten.** Tambah `ol.breadcrumb` di `page-header`; setiap halaman list wajib `page-actions` ("+ Tambah", "Export", "Cetak") — saat ini hanya halaman cetak yang punya.
10. **Empty/loading/error Tabler.** Ganti semua `alert-info "Belum ada…"` dengan pola `empty` (`empty-icon`, `empty-title`, `empty-subtitle`, `empty-action`) + tombol CTA; `@error` → `is-invalid + invalid-feedback`; tambah `role="alert"`/`aria-live`.
11. **Hapus satu-satunya inline style** (`livewire/pmb/daftar-publik.blade.php:3`) → `alert alert-success`; pindah `onclick` cetak/VA ke listener terpusat bila CSP diketatkan (fungsi tetap sama, tetap Tabler `btn`).
12. **Aksesibilitas & cetak.** Pastikan semua `input/select/textarea` punya `id+label for`; tombol ikon punya `aria-label`; kontras `text-white-50` → `text-white-75`/putih penuh untuk teks penting; halaman cetak (`krs/khs/transkrip/kwitansi/surat`) tambah kop institusi + `table-bordered` + tanda tangan + `d-print-none` sudah benar.

## Catatan verifikasi
- `grep style=` → 1 hasil (`livewire/pmb/daftar-publik.blade.php:3`).
- `grep onclick` → 7 hasil, semua `window.print()` (cetak/*, khs/*, krs/*, transkrip/*) + 2 pemanggil `bayar()/cek()` (`keuangan/bayar.blade.php:20,45`) — wajar, tidak ada framework asing.
- `grep wire:loading` di `livewire/*` → 0 hasil (konfirmasi tidak ada loading state).
- Tidak ditemukan class Tailwind/Bootstrap non-Tabler; seluruh layout memakai pola Tabler (`page`, `page-wrapper`, `page-header`, `page-body`, `card`, `badge`, `alert`, `table-responsive`, `row-cards`, `avatar`, `datagrid`-setara).
- 10 file Livewire dibaca penuh + 6 file tambahan (`skripsi/*`, `sidang/*`, `surat/*`, `bimbingan/log`) dikonfirmasi berpola sama; seluruh wrapper dibaca penuh.
