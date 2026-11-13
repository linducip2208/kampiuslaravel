# ROLES & PERMISSIONS — KAMPIUS
Sumber: database/seeders/RolesPermissionsSeeder.php
- superadmin: semua permission
- admin_prodi: master, pmb.view/verify, krs.manage/approve, skripsi, surat, pengumuman, laporan.view
- keuangan: keuangan.manage, laporan.view
- dosen: lms.manage, nilai.manage, skripsi.manage
- mahasiswa: pmb.view
- pimpinan: laporan.view
Perbaikan sesi ini:
- Sidebar dikelompokkan (DASHBOARD/AKADEMIK/KEUANGAN/OPERASIONAL/ANALITIK/SISTEM), hanya tampil bila hasAnyRole lolos + route exists.
- KRS dosen diarahkan ke dosen.krs.persetujuan (sebelumnya salah ke pengajuan mahasiswa).
- InputNilai: dosen hanya boleh kelas miliknya (cek Kelas.dosen_id vs Dosen.user_id), superadmin/admin_prodi bypass.
- KumpulTugas: mahasiswaId di-resolve ulang dari Auth di save() (anti-spoof client).
