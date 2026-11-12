# PROGRESS & TECH PLAN — SIMAK Kuliah

Stack: **Laravel 13 + MySQL 8 + Blade/Livewire 3/Alpine + Tabler UI + Sanctum API + Flutter**

## Milestone — Update 12 Nov 2026: SEMUA SELESAI, migrate OK, 46 test passed / 153 assertions, pint clean, smoke test OK

- [x] **P0:** Laravel 13.42 + Livewire + Spatie + Sanctum, MySQL `kuliah`, Tabler layout + sidebar real
- [x] **Auth web:** login/logout session + halaman login Tabler (demo: superadmin/dosen/mahasiswa@kampus.ac.id / password)
- [x] **P1 Auth+Master:** 6 role, master data + seeder demo
- [x] **P2 PMB:** form publik, verifikasi, pengumuman, daftar ulang
- [x] **P3 KRS+Jadwal:** + approval PA + block tunggakan/SKS + cetak
- [x] **P4 LMS+Absensi:** + QR + EDOM
- [x] **P5 Nilai+KHS:** + transkrip
- [x] **P6 Skripsi/KP:** + sidang + repository
- [x] **P7 Keuangan+Surat:** manual + VA + kwitansi + verifikasi QR publik
- [x] **P8 pSEO+API+Mobile+ComfyUI:** /prodi, /prodi/{slug}, /pengumuman, API v1 (13+3 routes), Flutter stub, ComfyUI+WAN 2.1 (job/command/halaman Generate Gambar, cover pengumuman, docs/COMFYUI.md)
- [x] **P9 Integrasi:** settings DB + halaman admin Integrasi, payment VA (simulated+Midtrans+callback), push (inbox+FCM+broadcast+API mobile), Feeder PDDikti (CSV+push), docs/INTEGRASI.md

- [ ] **P0 Scaffold (0.5 hari):** Laravel 13 install, MySQL `.env`, Tabler layout, Filament? NO — Tabler custom, Spatie permission, Livewire, Sanctum, docs ini
- [ ] **P1 Auth+Master (2 hari):** users, roles, fakultas/prodi, tahun/semester, ruang, matakuliah, kelas, import CSV
- [ ] **P2 PMB (2 hari):** gelombang, form publik, verifikasi, pengumuman, daftar ulang → mahasiswa
- [ ] **P3 KRS+Jadwal (2 hari):** KRS + approval PA + block tunggakan, jadwal + bentrok check, KRS PDF
- [ ] **P4 LMS+Absensi (3 hari):** pertemuan/materi/tugas/quiz, jurnal dosen, absensi QR+rekap, EDOM
- [ ] **P5 Nilai+KHS (2 hari):** input nilai, kunci, KHS/transkrip PDF, export Forlap
- [ ] **P6 Skripsi/KP (2 hari):** judul, pembimbing, log, sidang, revisi, repository
- [ ] **P7 Keuangan+Surat (2 hari):** tagihan massal, bayar manual+VA dummy, kwitansi, surat template+QR
- [ ] **P8 Dashboard+pSEO+API+Mobile (3 hari):** dashboard, halaman publik SEO, `/api/v1` + Flutter basic (login, jadwal, absensi QR, nilai, tagihan)

Total estimasi: ~18 hari dev solo. Lacak dengan checkbox di atas.

## Skema DB (inti, 30 tabel)

```
users(id, name, email, password, role)
roles, permissions (spatie)
fakultas(id, nama, kode)
prodi(id, fakultas_id, nama, jenjang, kode, slug)
tahun_akademik(id, tahun, semester_ganjil_genap, aktif)
mahasiswa(id, user_id, nim unique, prodi_id, angkatan, dosen_pa_id, status)
dosen(id, user_id, nidn unique, prodi_id, status)
matakuliah(id, prodi_id, kode, nama, sks, semester, prasyarat_id?)
kelas(id, matakuliah_id, tahun_id, nama, kuota, dosen_id, hari, jam_mulai, jam_selesai, ruang_id)
ruang(id, gedung, nama, kapasitas)
krs(id, mahasiswa_id, tahun_id, status: draft/diajukan/disetujui/ditolak, total_sks)
krs_detail(id, krs_id, kelas_id, status)
jadwal = via kelas (tambah tanggal_spesifik untuk pengganti)
pertemuan(id, kelas_id, ke, tanggal, topik, qr_token, qr_expires_at)
materi(id, pertemuan_id, judul, file_path/link)
tugas(id, pertemuan_id/kelas_id, judul, deadline, deskripsi)
tugas_kumpul(id, tugas_id, mahasiswa_id, file_path, nilai)
quiz(id, kelas_id, judul, durasi, deadline) + quiz_soal + quiz_jawaban
absensi(id, pertemuan_id, mahasiswa_id, status: hadir/telat/izin/alpa, scanned_at)
jurnal_dosen(id, pertemuan_id, realisasi, kendala)
nilai(id, mahasiswa_id, kelas_id, tugas, uts, uas, kehadiran, na, huruf, bobot) unique(mhs,kelas)
edom(id, mahasiswa_id, kelas_id, skor, kritik) unique(mhs,kelas,tahun)
pmb_gelombang(id, nama, tahun_id, tgl_buka, tgl_tutup, kuota, biaya)
pmb_pendaftar(id, no_daftar unique, nama, email, hp, prodi1_id, prodi2_id, gelombang_id, berkas_json, status, user_id?)
tagihan(id, mahasiswa_id, tahun_id, jenis: UKT/SPP/PMB/lain, nominal, terbayar, status)
pembayaran(id, tagihan_id, metode, bukti_path, status: pending/verified/rejected, verified_by)
skripsi_judul(id, mahasiswa_id, judul, deskripsi, status, pembimbing1_id, pembimbing2_id)
bimbingan(id, judul_id, dosen_id, catatan, file_path, status, tanggal)
sidang(id, judul_id, jenis: proposal/hasil/tutup, tanggal, ruang_id, berita_acara_path, nilai)
sidang_penguji(sidang_id, dosen_id, peran)
repository(id, mahasiswa_id, judul, abstrak, file_path, tahun, embargo?)
surat(id, mahasiswa_id, jenis, keperluan, nomor_surat, status, file_pdf)
pengumuman(id, prodi_id?, judul, isi, target: semua/mhs/dosen, published_at)
audit_logs(id, user_id, action, model, model_id, diff_json, created_at)
```

## API v1 (untuk Flutter)

```
POST /api/v1/login {nim_or_email, password} → token
GET  /api/v1/me
GET  /api/v1/jadwal-hari-ini
GET  /api/v1/krs-aktif + /khs/{tahun_id}
POST /api/v1/absensi/scan {qr_token}
GET  /api/v1/tugas-list + POST /api/v1/tugas/{id}/kumpul (multipart)
GET  /api/v1/nilai-ringkas (IPK, grafik per semester)
GET  /api/v1/tagihan + /pengumuman
GET  /api/v1/bimbingan + POST /api/v1/bimbingan (catatan+file)
```

Auth: Bearer Sanctum. Ability: `mobile`.

## Struktur Folder Laravel

```
app/Models/* (Mahasiswa, Dosen, Kelas, Krs, Nilai, dst)
app/Livewire/* (Pmb/*, Krs/*, Nilai/*, Keuangan/*, Skripsi/*)
app/Http/Controllers/Api/V1/* (Auth, Jadwal, Absensi, Nilai, Tugas, Tagihan)
resources/views/layouts/tabler.blade.php + livewire/* + public/prodi, berita
database/migrations/* (urut sesuai skema di atas)
routes/web.php (admin/dosen/mhs + publik) + routes/api.php (v1)
mobile/ (Flutter: login, home, jadwal, qr, nilai, tagihan)
```

## Definisi Selesai (DoD) per modul
Seed + Livewire CRUD jalan + validasi + PDF/export + API (kalau dipakai mobile) + test manual checklist di `docs/TESTING.md`.

## Langkah Berikutnya (pilih satu)
1. `composer create-project laravel/laravel` di folder ini + pasang Tabler + Spatie + Livewire (saya bisa jalankan)
2. Generate migration + model M1 dulu
3. Scaffold Flutter `mobile/` dasar
