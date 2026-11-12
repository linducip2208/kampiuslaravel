# Database — MySQL 8 (`kuliah`, ±50 tabel)

Normalisasi 3NF per domain: `fakultas→prodi→mahasiswa/dosen`, `matakuliah→kelas→pertemuan→materi/tugas/quiz`, `krs→krs_detail`, `nilai`, `skripsi_judul→bimbingan/sidang→sidang_penguji`, `tagihan→pembayaran`, `surat`, `pengumuman`, `notifikasi`, `settings`, `audit_logs`, Spatie (roles/permissions), Sanctum (personal_access_tokens).

## Konvensi
- FK memakai `constrained()` + `nullOnDelete()` untuk kolom nullable; hard-FK untuk relasi inti intra-modul. Modul LMS menyusul via migrasi forward (data dev konsisten; audit `AUDIT_DB.md §6` untuk daftar).
- Unique: `nim`, `no_daftar`, `qr_token`, `pembayaran.reference`, `surat.nomor_surat`, `prodi.slug`, `nilai(mhs,kelas)`, `absensi(pertemuan,mhs)`, `tugas_kumpul(tugas,mhs)`, `krs(mhs,tahun)`.
- Enum status terkunci di migrasi (lihat model masing-masing; jangan ubah nilai historis).
- `terbayar`/`status` tagihan hanya diubah lewat `KeuanganService` / `PaymentService` (transaction + `lockForUpdate` + idempotency).
- Index: FK + kolom filter rutin (`status`, `tahun_akademik_id`, `prodi_id`, `angkatan`, `published_at`).

## Seeding
`RolesPermissionsSeeder` (6 role + permission) → `MasterDataSeeder` (fakultas/prodi/tahun/ruang/MK/kelas + akun demo + role) → `DemoSeeder` (data end-to-end). Idempotent via `firstOrCreate`/`updateOrCreate`.
