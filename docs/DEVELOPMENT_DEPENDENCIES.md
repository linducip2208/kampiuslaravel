# DEVELOPMENT DEPENDENCIES (rantai antar-modul)

Format: A BUTUH B (alasan). Urutan implementasi mengikuti rantai ini.

## Fondasi → semua
- Semua modul BUTUH auth+RBAC (RolesPermissionsSeeder) — fondasi paling awal.
- Semua tulis finansial/akademik BUTUH transaksi + lock (pola KeuanganService::verifyPembayaran).
- Semua list BUTUH paginasi + guard Schema::hasTable (pola aman instalasi bertahap).

## Rantai PMB → mahasiswa → KRS → nilai → lulus → wisuda
1. PmbGelombang/kuota → Pendaftar → VA (PmbVaService) → verifikasi → tentukanKelulusan (lock kuota lulus).
2. daftarUlang → User + NIM + Tagihan awal → TANPA ini mahasiswa tidak bisa KRS/bayar.
3. KRS Pengajuan BUTUH: TahunAkademik aktif + Kelas + BatasSks + validasiKrs + Kalender lock.
4. PersetujuanPa BUTUH KRS diajukan + scope dosen_pa_id.
5. Nilai BUTUH KrsDetail/kelas + authorizeKelas; KunciNilai BUTUH Nilai.
6. Yudisium BUTUH: IPK (AkademikService::ringkasan) + tunggakan nol + sidang lulus.
7. Wisuda BUTUH status lulus + batch dibuka + kuota (lock) → PIN ijazah.
8. Alumni + SKL dibuat DALAM transaksi yudisium yang sama.

## Rantai uang
1. TagihanMassal BUTUH Mahasiswa + TahunAkademik + UktTemplate + MahasiswaBeasiswa.
2. PaymentService BUTUH Tagihan + Setting kredensial + JurnalKeuangan (postPenerimaan).
3. Verifikasi BUTUH Pembayaran pending → Tagihan + Cicilan + Jurnal atomik.
4. Rekonsiliasi BUTUH Pembayaran.reference stabil.
5. Neraca BUTUH JurnalKeuangan terisi (post/settle).
6. Denda BUTUH DendaRule + cron `denda:terapkan`.

## Rantai dokumen
1. Surat nomor BUTUH jenis+counter; verify BUTUH TTE payload stabil.
2. KHS/Transkrip BUTUH Nilai terkunci + tahun akademik.
3. KTM BUTUH Mahasiswa + NIM.

## Rantai modul baru (belum ada — jangan mulai dari tengah)
- Kuis/ujian BUTUH: bank soal → paket → percobaan → nilai → kirim ke SIAKAD.
- OBE BUTUH: CPL → CPMK → pemetaan → asesmen → agregat (berurutan, tak bisa dilompat).
- Riset BUTUH: proposal → reviewer → dana → luaran. Pengmas BUTUH: proposal → tim → LPJ.
- GL penuh BUTUH: CoA → jurnal umum → tutup periode (berurutan).
- AI BUTUH: knowledge base → RAG → guardrail → asisten spesifik (tanpa ini = template statis).
- Webhook keluar BUTUH: antrean + log + replay SEBELUM klaim reliabel.

## Aturan orkestrasi
Satu agen = satu area file eksklusif (lihat daftar OWNERSHIP di bawah).
Integrator (ronda ini: penulis CHANGELOG/FINAL) memverifikasi tidak ada
duplikasi tabel/kolom/route antar-modul sebelum merge.
File sensitif bersama (layouts/tabler, web.php, DashboardController)
hanya disentuh satu pihak per ronde, dengan full suite hijau sebagai gerbang.
