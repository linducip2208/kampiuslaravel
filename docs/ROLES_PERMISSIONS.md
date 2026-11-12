# Role & Permission

Role (Spatie, guard `web`): `superadmin`, `admin_prodi`, `keuangan`, `dosen`, `mahasiswa`, `pimpinan`.

## Matriks (route group + sidebar + Livewire)
| Area | Role |
|---|---|
| Dashboard | semua user login |
| PMB verifikasi, Integrasi | superadmin, admin_prodi |
| KRS mahasiswa, bayar, kumpul tugas, absensi, skripsi pengajuan, bimbingan, sidang daftar, surat pengajuan | mahasiswa (+ staf pendamping) |
| KRS persetujuan, LMS, nilai, set pembimbing, nilai sidang | dosen (+ staf akademik) |
| Tagihan massal, verifikasi bayar, nomor surat | keuangan (+ admin prodi, superadmin) |
| Dashboard pimpinan, PDDikti | pimpinan, superadmin |
| Broadcast, generate gambar | staf internal (tanpa mahasiswa) |
| Cetak KHS/transkrip/KRS/kwitansi/surat | auth + **kepemilikan**: milik sendiri, atau staf (`AuthorizesMahasiswaData`) |

Livewire sensitif memakai trait `RequiresRoles` (update-request Livewire tidak lewat route middleware).
API memakai ability Sanctum `mobile` + expiry 30 hari + throttle.

## Aturan
- Sembunyikan menu BUKAN izin — sidebar difilter per role, server tetap menolak (403) bila lolos UI.
- Owner-scope: mahasiswa hanya datanya sendiri (KHS, KRS, tugas, absensi, bimbingan divalidasi ulang di mount/save).
- Test: `SecurityTest` (6 test IDOR + role + tamu).
