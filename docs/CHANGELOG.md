# CHANGELOG
## 2026-11-13 — Hardening transaksi + sidebar grup + dashboard premium + factory fix
- Test-infra: TahunAkademikFactory deterministik unik (perbaiki flaky unique tahun+semester di KeuanganPhase2Test).
## 2026-11-13 (ronde 3) — N+1 + otorisasi + SEO + tes hardening
- Eager load: PersetujuanPa details.kelas.matakuliah + mahasiswa.user/prodi; VerifikasiBayar nested tahunAkademik + jurnal.pembayaran.tagihan.
- KunciNilai: authorize kelas + buka() hanya superadmin/admin_prodi; InputNilai pola sama.
- ImporCsv tagihan: tolak timpa nominal bila sudah ada pembayaran/terbayar.
- Api Tugas: index owner-scope (mahasiswa/dosen), kumpul wajib mahasiswaId (403 bila tanpa akun).
- SEO: meta description + Open Graph di layout.
- Tes baru HardeningTest (3 tes): VA lunas 422, dosen tidak bisa buka kunci, dosen tidak bisa input kelas orang lain.
## 2026-11-13 (ronde 4) — soft delete keuangan + N+1/otorisasi/API/SEO
- Migrasi aman 2026_11_13_145235 (guarded hasTable/hasColumn): softDeletes tagihan/pembayaran/jurnal_keuangan; model + SoftDeletes.
- Eager/N+1, KunciNilai, ImporCsv guard, Api Tugas scope, SEO meta/OG.
- Docs: ACADEMIC_WORKFLOWS, FINANCIAL_WORKFLOWS, PERFORMANCE, FINAL_SCORECARD.
## 2026-11-13 (ronde 6) — index performa + tes soft-delete + deploy doc
- Migrasi aman 2026_11_13_145704 (guarded + try/catch): index pembayaran/tagihan/krs/mahasiswa/pmb status & komposit.
- HardeningTest +1: soft-delete tagihan mengarsip (assertSoftDeleted + withTrashed).
- Docs: DEPLOYMENT.md.
## 2026-11-13 (ronde 7) — konfirmasi aksi destruktif
- Tambah wire:confirm pada: surat terbitkan/selesai/tolak, KRS setujui/tolak, verifikasi/tolak bayar, cuti setujui/tolak, pinjam ruang setujui/tolak.
- Audit: seluruh tombol hapus/reset/lulus/kunci/refund sebelumnya sudah berkonfirmasi; ronde ini menutup 5 celah sisanya.
## 2026-11-13 (ronde 8) — gateway retry + panel peran
- PaymentService: callDriverWithRetry 3x (200ms jeda) untuk createTransaction & checkStatus; gagal permanen -> RuntimeException (422/502 konsisten).
- HardeningTest +1: driver flaky gagal 2x lalu sukses (3 calls, reference benar).
- Dashboard strip kini role-aware (keuangan/KRS/PMB/mahasiswa/pimpinan).
## 2026-11-13 (ronde 9) — loading state aksi async
- Spinner + disable pada: generate tagihan, terapkan denda, rekonsiliasi CSV, verifikasi/tolak bayar, impor CSV, simpan nilai, setujui/tolak KRS.
- Cegah double-submit saat request Livewire berjalan.
## 2026-11-13 (ronde 10) — empty-state PMB + guard finansial teruji
- DaftarPublik: peringatan jelas bila tidak ada gelombang dibuka.
- CalculationTest +2: denda clamp persen/maksimal/nonaktif/belum-jatuh-tempo; beasiswa tidak melebihi tagihan.
## 2026-11-13 (ronde 11) — kelola aturan denda via UI
- Livewire keuangan.denda-rule-kelola + route keuangan.denda + menu sidebar: CRUD aturan (TA/jenis/min-telat/mode/nilai/maksimal/aktif), validasi persen maks 100%.
- DendaRuleTest (4 tes): simpan, tolak persen >100, mahasiswa 403, hapus.
## 2026-11-13 (ronde 12) — bukti benchmark performa
- DashboardPerfTest: dashboard = 79 query (ambang regresi 90), index MySQL terverifikasi live.
## 2026-11-13 (ronde 13) — denda benar-benar otomatis
- Command denda:terapkan [--tahun] + jadwal harian 01:00 (tanpa overlap) + audit scheduler.
- DendaOtomatisTest: terapkan 50rb, catat audit, idempoten di jalan kedua.
## 2026-11-13 (ronde 14) — insiden migrasi + health guard
- Insiden: deleted_at 500 di MySQL dev karena migrasi 145235 belum dijalankan (tes SQLite hijau menutupi). Migrasi dijalankan + kolom/index terverifikasi live.
- /health kini lapor pending_migrations (503 bila >0); HealthTest kunci regresi.
## 2026-11-13 (ronde 15) — kunci regresi middleware rute
- RouteGuardTest (2 tes, 54 asersi): 16 rute sensitif wajib auth+role; 3 rute publik tetap terbuka.
- Menutup kelas bug audit: controller tanpa auth di file aman hanya via middleware rute.
## 2026-11-13 (ronde 16) — revisi KRS ditolak teruji
- KrsFlowTest +1: KRS DITOLAK bisa diajukan ulang (kembali DIAJUKAN, detail diperbarui); DISETUJUI tetap terkunci.
## 2026-11-13 (ronde 5) — tes kalkulasi + SEO/API + aksesibilitas + audit link
- CalculationTest (4 tes): hitungNa/bobot, konversi huruf, batas SKS IPK, sisa/total tagihan, remedial D/E maks 55.
- Aksesibilitas: skip-link + #main-content di layout.
- API docs: contoh login/tagihan/VA/callback + error standar.
- Audit: semua route sidebar + welcome terverifikasi ada (tidak ada dead link).
- Dashboard utama: stat real, performa KRS, earnings, donut angkatan, messages, activity.
- Sidebar grup permission-aware; KRS dosen -> persetujuan; brand KAMPIUS.
- Pimpinan dashboard: 12 kartu + keuangan ringkas + tren.
- Fix: KumpulTugas anti-spoof, VA tolak lunas, authorize tagihan, InputNilai ownership+transaction, generateMassal transaction+race-safe, BayarManual dedup pending+transaction+cleanup file, PMB store/submit transaction+lock kuota, Verifikasi status/kelulusan transaction+lock, Sidang setStatus transaction.
- Landing: judul SEO KAMPIUS + hero + footer brand.
- Docs: 10 file di docs/.
