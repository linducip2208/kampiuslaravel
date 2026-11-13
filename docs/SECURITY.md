# SECURITY — temuan & perbaikan (Nov 2026)
## Diperbaiki
1. KumpulTugas spoof mahasiswaId (critical) — save() resolve ulang dari Auth.
2. VA untuk tagihan lunas — abort_if lunas di BayarController::createVa + Api V1 TagihanController.
3. authorizeTagihan terlalu sempit — kini owner ATAU keuangan/superadmin/admin_prodi.
4. InputNilai: tambah authorizeKelas + DB::transaction + preload lock (hindari partial-save & cross-kelas write).
## Masih perlu (tidak diubah agar aman)
- PmbController::store & DaftarPublik: belum ada transaction + kuota race (perlu lockForUpdate).
- TagihanMassal, BayarManual, Pmb Verifikasi, NilaiSidang: tambah transaction/idempotency.
- LaporanController & PmbFunnelController: tidak ada auth di file — aman HANYA karena route di group role pimpinan|superadmin (+keuangan untuk laporan). Jangan pindahkan route keluar group.
- ImporCsv tagihan menimpa nominal tanpa cek terbayar/verified.
