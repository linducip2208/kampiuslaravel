# FINAL_RBAC_MATRIX — v1 (draft, dibuat dari `RolesPermissionsSeeder`)

> Status: peran baru batch 1 SUDAH di-seed + bisa ditetapkan via
> Admin → Kelola User (daftar peran dinamis dari DB). Wiring berbasis
> peran per modul = batch 2. Tanpa permission tenant (single-institution).

Legenda wiring: **P** = gate `permission:`/`can:` aktif, **R** = grup
`role:` aktif, **-** = label/penugasan saja (gate menyusul).

| Role | master.manage | pmb.view | pmb.verify | krs.manage | krs.approve | lms.manage | nilai.manage | nilai.lock | skripsi.manage | skripsi.approve | keuangan.manage | surat.manage | pengumuman.manage | laporan.view | Wiring |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| superadmin | x | x | x | x | x | x | x | x | x | x | x | x | x | x | R+P |
| admin_prodi | x | x | x | x | x | - | x | - | x | - | - | x | x | x | R+P |
| keuangan | - | - | - | - | - | - | - | - | - | - | x | - | - | x | R |
| dosen | - | - | - | - | - | x | x | - | x | - | - | - | - | - | R |
| mahasiswa | - | x | - | - | - | - | - | - | - | - | - | - | - | - | R |
| pimpinan | - | - | - | - | - | - | - | - | - | - | - | - | - | x | R+P |
| rektor | - | - | - | - | - | - | - | - | - | - | - | - | - | x | P |
| warek | - | - | - | - | - | - | - | - | - | - | - | - | - | x | P |
| dekan | - | - | - | - | - | - | - | - | - | - | - | - | x | x | P |
| wadek | - | - | - | - | - | - | - | - | - | - | - | - | x | x | P |
| kaprodi | x | x | x | x | x | x | x | - | x | - | - | x | x | x | P |
| kajur | x | x | x | x | x | x | x | - | x | - | - | x | x | x | P |
| penguji | - | - | - | - | - | - | x | - | x | - | - | - | - | - | - |
| kasir | - | - | - | - | - | - | - | - | - | - | x | - | - | - | - |
| akuntansi | - | - | - | - | - | - | - | - | - | - | x | - | - | x | P |
| hrd | - | - | - | - | - | - | - | - | - | - | - | - | x | x | - |
| auditor | - | - | - | - | - | - | - | - | - | - | - | - | - | x | P |
| alumni | - | - | - | - | - | - | - | - | - | - | - | - | - | - | - |
| orangtua | - | - | - | - | - | - | - | - | - | - | - | - | - | - | - |

Catatan jujur:
- Gate `can:laporan.view` dipakai di `/admin/audit` + semua ekspor
  `LaporanController` — peran ber-`laporan.view` (rektor/warek/auditor/
  akuntansi/...) LANGSUNG berfungsi di sana hari ini (teruji).
- Gate `role:` di `routes/web.php` belum memuat peran baru — disengaja
  (batch 2, per modul + tes IDOR per peran). Jangan tambahkan peran ke
  grup route tanpa tes akses.
- `alumni`/`orangtua` = label penugasan (portal publik + halaman karier
  tidak butuh gate peran); fungsional saat permission modulnya ada.
- 6 peran lama TIDAK diubah (diuji regresi di `RbacExpansionTest`).

Batch 2 (belum): wiring `kaprodi`/`kajur` ke grup dosen+admin_prodi,
`penguji` ke sidang/nilai, `kasir`/`akuntansi` ke grup keuangan per-aksi,
`hrd` ke payroll/rekrutmen, UI matriks di admin, histori audit penugasan.
