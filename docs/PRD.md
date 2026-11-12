# PRD — SIAKAD Terpadu Kampus (Laravel 13 + Tabler + Flutter)

**Nama kode:** SIMAK Kuliah
**Lokasi:** `D:\project laravel\kuliah` (+ `mobile/` untuk Flutter)
**Status:** PRD v1.0 — 12 Nov 2026
**Stack wajib:** Laravel 13, MySQL 8, Blade + Livewire 3 + Alpine + Tabler UI, API Sanctum untuk Flutter, Redis queue/cache

## 1. Visi
Satu aplikasi untuk seluruh siklus mahasiswa jurusan/universitas: dari PMB → KRS/kuliah → nilai → skripsi/KP → lulus + administrasi keuangan & surat. Web untuk admin/dosen, mobile Flutter untuk mahasiswa/dosen.

## 2. User & Role
| Role | Akses |
|---|---|
| Superadmin | semua, kelola user/role, tahun akademik |
| Admin Prodi/Jurusan | master data prodi, verifikasi PMB, KRS, jadwal, sidang |
| Keuangan (BAAK) | tagihan UKT/SPP, pembayaran, tunggakan, lock KRS |
| Dosen (+ Dosen PA) | jadwal mengajar, absensi & jurnal, materi/tugas/quiz, input nilai, bimbingan skripsi/KP, sidang |
| Mahasiswa | KRS, jadwal, LMS, absensi QR, KHS/transkrip, tagihan, surat, skripsi/KP, EDOM |
| Calon Maba (guest) | daftar PMB, upload berkas, cek kelulusan, daftar ulang |
| Auditor/Pimpinan | dashboard & laporan read-only, export PDDikti/Forlap |

Auth: Laravel Breeze/Fortify + Spatie Permission. Login NIM/NIDN + email + password. Sanctum token untuk mobile.

## 3. Modul & Fitur (Wajib Semua)

### M1 — Auth, User, Master Data
- Login/logout, reset password, profil, ganti password
- CRUD user, role & permission (Spatie)
- Master: Universitas/Fakultas/Prodi, Tahun Akademik/Semester (aktif/nonaktif), Gedung/Ruang, Kurikulum, Matakuliah (kode, SKS, prasyarat), Kelas/Rombel, Kalender akademik
- Import CSV mahasiswa/dosen/matakuliah

### M2 — PMB (Penerimaan Maba)
- Gelombang PMB (nama, kuota prodi, tanggal, biaya formulir)
- Form daftar publik (pSEO): biodata, sekolah asal, pilihan prodi 1-2, upload ijazah/KK/KTP/pas foto
- Verifikasi berkas admin, status: draft/diajukan/diverifikasi/ditolak/lulus/tidak
- Pengumuman kelulusan publik via nomor pendaftaran
- Daftar ulang → auto-create akun mahasiswa + NIM + tagihan awal
- Laporan pendaftar per prodi/gelombang, export Excel

### M3 — Kemahasiswaan & KRS
- Profil mahasiswa: NIM, angkatan, PA, status (aktif/cuti/DO/lulus/nonaktif)
- KRS online: ambil MK sesuai kurikulum & semester, cek SKS maks by IPK, cek tunggakan (block jika nunggak), approval Dosen PA
- Jadwal kuliah per kelas (hari, jam, ruang, dosen), bentrok check
- Cetak KRS PDF

### M4 — LMS + Absensi Perkuliahan
- Per pertemuan (1-16): materi (file/link), tugas (deadline, upload jawaban, nilai), quiz PG/essay + auto-score PG
- Jurnal perkuliahan dosen (realisasi materi)
- Absensi mahasiswa: QR per pertemuan + PIN + window waktu + toleransi telat, rekap kehadiran, batas <75% warning
- EDOM: kuesioner evaluasi dosen oleh mahasiswa per semester

### M5 — Nilai, KHS, Transkrip
- Input nilai dosen: komponen (tugas/UTS/UAS/kehadiran) → NA → huruf (A-E) + bobot
- Kunci nilai per semester, revisi via admin
- KHS per semester (PDF), Transkrip sementara/akhir + IPK/IP, SKL info
- Export nilai PDDikti/Forlap CSV

### M6 — Skripsi / KP / Magang Hub
- Pengajuan judul (mahasiswa) → approval prodi → assign Pembimbing 1/2 + Penguji
- Log bimbingan (tanggal, catatan, file, status disetujui/revisi)
- Syarat sidang check (SKS lulus, TOEFL, bebas tunggakan — otomatis)
- Pendaftaran sidang (jadwal, ruang, penguji), berita acara + nilai sidang, revisi pasca-sidang
- Repository: upload final skripsi/jurnal (PDF), embargo, pencarian full-text (Meilisearch optional)

### M7 — Keuangan (UKT/SPP)
- Template tagihan per semester/prodi/angkatan, generate massal
- Metode: manual transfer (upload bukti + verifikasi) + Virtual Account dummy adapter (siap integrasi)
- Riwayat bayar, kwitansi PDF, flag tunggakan → block KRS & sidang
- Dashboard tunggakan per prodi

### M8 — Surat & Helpdesk
- Pengajuan surat: aktif kuliah, pengantar KP/magang, keterangan lulus, legalisir
- Nomor surat auto, template PDF + QR verifikasi, tracking status
- Ticketing helpdesk BAAK (mahasiswa → admin)

### M9 — Pengumuman, Notifikasi, Berita (pSEO)
- Pengumuman internal per prodi/angkatan + push ke mobile
- Halaman publik: profil jurusan/prodi, daftar prodi, berita/kegiatan, jadwal PMB — SEO routes (`/prodi/{slug}`, `/berita/{slug}`)
- Adapter notifikasi: mail + WA/FCM via admin setting (no hardcode provider)

### M10 — Dashboard & Laporan
- Superadmin/pimpinan: total mhs/dosen, PMB funnel, kehadiran, tunggakan, kelulusan
- Dosen PA: wali list + IPK + SKS
- Export: Excel/PDF semua rekap

### M11 — Mobile Flutter (mahasiswa + dosen)
- Login Sanctum (NIM + password), biometrik optional
- Home: jadwal hari ini, tagihan menunggak, pengumuman
- KRS: lihat status, ajukan (webview atau native bertahap)
- Absensi: scan QR pertemuan
- LMS ringkas: materi/tugas/quiz list + upload tugas (file picker)
- Nilai: KHS/IPK chart, transkrip
- Bimbingan: chat log + upload revisi
- Notif push (FCM), profil + kartu mahasiswa digital (QR NIM)

## 4. Non-Fungsional
- PHP 8.3+, Laravel 13, Livewire 3, Tabler UI, MySQL 8 (utf8mb4), Redis queue (notif, PDF, export), Horizon
- Storage S3-compatible/R2 via env, bukan hardcode
- Audit log aktivitas sensitif (nilai, keuangan, sidang)
- Responsive Tabler, bahasa ID, timezone Asia/Jakarta
- API versioned `/api/v1`, rate-limit, Sanctum abilities per role

## 5. Di Luar Scope v1
- Payment gateway real, SSO PDDikti live-sync, video conference built-in, plagiarism checker, iOS push production cert.
