# AUDIT MODUL AKADEMIK & FINANSIAL — SIMAK Kuliah

Tanggal: 12 Nov 2026. Metode: baca kode (bukan klaim docs). Sumber: `docs/PRD.md`, `docs/PROGRESS.md`, `docs/INTEGRASI.md`, `app/` + `routes/` + `tests/Feature/`.

Legenda: ✅ Ada & jalan (kode + test) · ⚠️ Ada tapi cacat/rusak · ❌ Hilang (PRD menuntut, kode tidak ada).

---

## 1. Matriks PRD vs Realita

### M2 — PMB

| Fitur PRD | Status | Bukti |
|---|---|---|
| Form daftar publik | ✅ | `PmbController@store`, `Livewire/Pmb/DaftarPublik.php` |
| Gelombang + cek tanggal buka | ✅ | `PmbGelombang::isBuka()` dipakai di store/submit |
| Verifikasi berkas admin | ✅ | `Livewire/Pmb/Verifikasi::ubahStatus()` |
| Status draft/diajukan/diverifikasi/ditolak/lulus/tidak | ✅ | `PmbPendaftar::STATUSES`, test `PmbFlowTest` |
| Pengumuman kelulusan publik via no_daftar | ✅ | `PmbController@cek`, test cek |
| Laporan pendaftar per prodi/gelombang + export Excel | ❌ | Tidak ada route/controller/export; hanya tabel Livewire |
| **Daftar ulang → auto-create akun + NIM + tagihan awal** | ⚠️ **PUTUS** | `Verifikasi::daftarUlang()` hanya `User::firstOrCreate + update user_id`. **Tidak membuat `mahasiswa` (NIM), tidak membuat tagihan awal.** Kolom yang dibutuhkan (`prodi pilihan`, `gelombang→tahun_akademik`) tersedia tapi tidak dipakai |
| Upload berkas (ijazah/KK/KTP/pas foto) | ❌ | Kolom `berkas_json` ada di migrasi/model tapi **tidak ada input upload** di `PmbController@store` maupun `DaftarPublik` (hanya biodata teks) |
| Kuota gelombang dicek | ❌ | Kolom `kuota` ada, tidak pernah dibaca saat verifikasi/kelulusan |

### M3 — KRS / Kemahasiswaan

| Fitur PRD | Status | Bukti |
|---|---|---|
| KRS online + simpan draft/diajukan | ✅ | `Livewire/Krs/Pengajuan::ajukan()` (selalu status `diajukan`), test `KrsFlowTest` |
| Cek SKS maks by IPK | ✅ | `Krs::sksMaksByIpk()` + cek di `ajukan()` + test |
| Blokir tunggakan | ✅ | `Pengajuan::adaTunggakan()` + `KeuanganService::isBlockedForKrs()`, test |
| Approval Dosen PA | ✅ | `Livewire/Krs/PersetujuanPa` (setujui/tolak + catatan), test |
| Cetak KRS PDF | ⚠️ | `KrsController@cetak` return **view HTML print-friendly**, bukan PDF (tanpa package — sesuai komentar kode, tapi PRD minta PDF) |
| Jadwal kuliah per kelas (hari/jam/ruang/dosen) | ✅ (data) | Kolom ada di tabel `kelas`; **tidak ada CRUD jadwal / halaman jadwal** di `routes/web.php` |
| **Bentrok jadwal check** | ❌ | Tidak ada perbandingan hari+jam di `Pengajuan`; `grep bentrok/overlap` nihil |
| **Cek prasyarat MK** | ❌ | `matakuliah.prasyarat_id` + relasi ada, **tidak pernah dicek** saat ajukan KRS |
| **Cek kuota kelas** | ❌ | Kolom `kuota` ada, tidak dicek di `ajukan()` |
| **Revisi KRS** | ❌ | Setelah `disetujui`, tidak ada alur ubah/batal-tambah; `ajukan()` me-`delete()` details lalu tulis ulang (bisa menimpa KRS yang sudah disetujui tanpa validasi status) |
| Profil mhs (NIM/angkatan/PA/status) | ✅ (data) | Model `Mahasiswa` ada; tidak ada halaman profil khusus |
| Status cuti/DO/lulus/nonaktif | ⚠️ | Kolom `status` string bebas, tidak ada transisi/state machine; `generateMassal` hanya ambil `aktif` |

### M4 — LMS + Absensi

| Fitur PRD | Status | Bukti |
|---|---|---|
| Materi / tugas / quiz + kumpul + nilai tugas | ✅ | `Livewire/Lms/*`, `Api/V1/TugasController`, `TugasKumpul` |
| Jurnal dosen | ✅ | Model `JurnalDosen` (CRUD tidak diaudit dalam — file ada) |
| Absensi QR + PIN + window + toleransi telat | ⚠️ | `AbsensiService::scan()`: token + expiry + window + telat>15 mnt ✅; **PIN tidak ada** di `pertemuan` (grep nihil) |
| Rekap kehadiran, warning <75% | ❌ | Tidak ada rekap/persentase/warning di kode |
| EDOM | ⚠️ | Model + tabel `edom` ada; **tidak ada UI/input/route EDOM** di `routes/web.php` |
| Quiz auto-score PG | ❌ (belum diverifikasi dalam) | `quiz_*` tabel ada; logika skor tidak diaudit — anggap parsial |

### M5 — Nilai, KHS, Transkrip

| Fitur PRD | Status | Bukti |
|---|---|---|
| Input komponen (tugas/UTS/UAS/kehadiran) → NA → huruf + bobot | ✅ | `Nilai::hitungNa/konversiHuruf/bobotHuruf`, `Livewire/Nilai/InputNilai`, test `NilaiTest` |
| Kunci nilai + revisi via admin | ✅ | `Livewire/Nilai/KunciNilai` (kunci/buka), `InputNilai::save()` skip `is_locked` |
| KHS per semester (PDF) | ⚠️ | `KhsController@show`: **(a)** bukan PDF (view HTML), **(b)** tidak filter per semester (ambil semua nilai mhs), **(c)** `totalSks = 0` hardcode, **(d)** IPK = `avg(bobot)` tanpa bobot SKS — **salah rumus** |
| Transkrip + IPK | ⚠️ | `TranskripController@cetak`: bukan PDF, IPK = `avg(bobot)` tanpa bobot SKS — **salah rumus** (API mobile `NilaiController::ringkas` justru **benar**: Σ(bobot×SKS)/ΣSKS) |
| SKL info | ❌ | Tidak ada |
| Export nilai PDDikti/Forlap CSV | ✅ | `PddiktiService::exportCsv('nilai')` + route `/pddikti/export/nilai` |
| Syarat kelulusan (SKS lulus, IPK min, dll) | ❌ | Tidak ada pengecekan syarat kelulusan di mana pun |

### M6 — Skripsi / KP / Magang

| Fitur PRD | Status | Bukti |
|---|---|---|
| Pengajuan judul → approval prodi | ✅ | `PengajuanJudul` + `SetPembimbing` (status diajukan/disetujui/ditolak/revisi), test `SkripsiFlowTest` |
| Assign Pembimbing 1/2 + Penguji | ⚠️ | Pembimbing 1/2 ✅ via `SetPembimbing`; **penguji hanya ditempel per-sidang** (`SidangPenguji`), bukan penetapan awal |
| Log bimbingan | ✅ | `Livewire/Bimbingan/Log` (pending/dis setujui/revisi) |
| Syarat sidang otomatis (SKS lulus, TOEFL, bebas tunggakan) | ⚠️ | Hanya **bebas tunggakan** (`Pendaftaran::save` via `isBlockedForSidang`); **SKS lulus & TOEFL tidak dicek** (tidak ada kolom TOEFL/SKS-lulus) |
| Pendaftaran sidang (jadwal/ruang/penguji) | ✅ | `Livewire/Sidang/Pendaftaran` + `NilaiSidang::addPenguji` |
| Berita acara + nilai sidang | ⚠️ | Nilai rata-rata penguji ✅ (`recalc`); **berita acara hanya kolom `berita_acara_path`**, tidak ada generate/upload BA |
| Revisi pasca-sidang | ⚠️ | Status `revisi` ada di `sidang.status` tapi **tidak ada alur upload revisi / approval revisi** |
| Repository final (PDF, embargo, full-text) | ⚠️ | Model + tabel `repository` (+`embargo`) ada; **tidak ada UI upload/cari** (tidak ada Livewire/route/view repository; full-text/Meilisearch nihil) |
| KP/Magang | ❌ | Tidak ada jejak KP/magang (hanya jenis surat `pengantar_kp`) |

### M7 — Keuangan

| Fitur PRD | Status | Bukti |
|---|---|---|
| Template tagihan + generate massal | ✅ | `KeuanganService::generateMassal` + `Livewire/Keuangan/TagihanMassal` (filter prodi/angkatan, idempoten via unique), test |
| Manual transfer (upload bukti + verifikasi) | ⚠️ **PUTUS** | Kolom `bukti_path` ada; `VerifikasiBayar` + `verifyPembayaran()` jalan (test ✅). Tapi **tidak ada form upload bukti / pembuatan `pembayaran` manual** oleh mahasiswa — satu-satunya pembuat `pembayaran` adalah `PaymentService::createVa` |
| VA adapter (dummy/siap integrasi) | ✅ | `PaymentService` + 4 driver (simulated/tripay/midtrans/xendit), callback + `syncStatus`, test `IntegrasiTest` |
| Riwayat bayar + kwitansi PDF | ⚠️ | Riwayat di `BayarController@index` ✅; kwitansi = **view HTML** (`KwitansiController@cetak`, gate `verified` ✅), bukan PDF |
| Flag tunggakan → blokir KRS & sidang | ✅ | `KeuanganService::isBlockedForKrs/Sidang`, dipakai di `Pengajuan` + `Pendaftaran` |
| Dashboard tunggakan per prodi | ⚠️ | `dashboard.pimpinan` hanya hitung global (`tagihan_belum_bayar`), **tidak per prodi**; welcome page ada `piutang` rupiah global |
| Beasiswa/diskon/potongan | ❌ | Nihil total (grep kosong) |

### M8 — Surat & Helpdesk

| Fitur PRD | Status | Bukti |
|---|---|---|
| Pengajuan surat (4 jenis) | ✅ | `Livewire/Surat/Pengajuan` |
| Nomor surat otomatis | ✅ | `SuratNomorService::generate` (`JENIS/UNIV/TAHUN/SEQ`, `lockForUpdate`) + `NomorOtomatis` Livewire, test |
| Template PDF + QR verifikasi | ⚠️ | Template = view HTML (`cetak.surat`); QR/verify publik ✅ (`SuratController@verify`, route `/surat/verifikasi/{nomor}`) — QR image-nya tergantung view, bukan PDF |
| Tracking status | ✅ | diajukan→diproses→selesai/ditolak |
| Ticketing helpdesk BAAK | ❌ | Nihil (tidak ada tabel/route/UI tiket) |

### M9–M11 — Pengumuman/Notifikasi, Dashboard, Mobile, PDDikti, Kalend
...[truncated 3881 chars]
