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
| rektor | - | - | - | - | - | - | - | - | - | - | - | - | - | x | R+P |
| warek | - | - | - | - | - | - | - | - | - | - | - | - | - | x | R+P |
| dekan | - | - | - | - | - | - | - | - | - | - | - | - | x | x | R+P |
| wadek | - | - | - | - | - | - | - | - | - | - | - | - | x | x | R+P |
| kaprodi | x | x | x | x | x | x | x | - | x | - | - | x | x | x | R+P |
| kajur | x | x | x | x | x | x | x | - | x | - | - | x | x | x | R+P |
| penguji | - | - | - | - | - | - | x | - | x | - | - | - | - | - | R (jalur mandiri owner-scope) |
| kasir | - | - | - | - | - | - | - | - | - | - | x | - | - | - | R+P |
| akuntansi | - | - | - | - | - | - | - | - | - | - | x | - | - | x | R+P |
| hrd | - | - | - | - | - | - | - | - | - | - | - | - | x | x | R (grup /hrd/*, bukan grup keuangan penuh) |
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

Batch 2 (SELESAI, bukti `tests/Feature/RbacWiringTest.php`, 8 tes):
wiring `kaprodi`/`kajur` ke grup dosen + komponennya,
`kasir`/`akuntansi` ke grup keuangan + komponennya,
`rektor`/`warek`/`dekan`/`wadek` ke grup pimpinan + komponen eksekutif/tracer,
audit `pddikti.push`, IDOR kepemilikan tetap menang atas peran.
Batch 3 SELESAI (bukti `tests/Feature/RbacBatch4Test.php`, 7 tes):
jalur examiner mandiri owner-scope (`Sidang/NilaiPenguji` +
`/sidang/nilai-saya`, tanpa akses NilaiSidang penuh), halaman HRD
(`/hrd/payroll`, `/hrd/rekrutmen`, komponen dibuka, grup keuangan tidak),
UI matriks baca-saja (`Admin/MatriksRbac`), audit penugasan
(`user.role.diubah` + diff), delegasi berbatas waktu (`delegasi_akses` +
`delegasi:sinkron` harian, anti-cabut-manual) + `permission:review`,
scoping OBE per prodi (+ anti-bocor BKD), audit baca dokumen
(`audit_baca` + hook KHS/transkrip).
Belum (batch berikut, bila dibutuhkan): delegasi/ekspirasi manual via UI
(saat ini CLI terjadwal + audit cukup), scoping prodi menyeluruh
(`dekan`, OBE penuh), audit baca dokumen yang lebih granular,
permission review otomatis berkala.
Catatan ronde ini: gate lapis-2 Livewire (`allowedRoles`) ikut diperbarui —
rute saja memberi rasa aman palsu (terbukti tes merah sebelum fix);
BKD + riset menolak akun tanpa baris dosen (anti null===null IDOR).
