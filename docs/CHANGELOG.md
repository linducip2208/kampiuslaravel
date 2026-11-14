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
## 2026-11-13 (ronde 17) — bug dokumen 404 + guard traversal
- PublikController: kunci ARSITEKTUR tidak cocok nama file (ARCHITECTURE.md) — semua link arsitektur 404. Perbaiki + DokumenTraversalTest (valid 200, traversal 404).
## 2026-11-13 (ronde 18) — scope pribadi dashboard mahasiswa
- DashboardController: hitung outstanding/tagihan/KRS milik sendiri (owner-scope); sebelumnya kartu "Tagihan Saya" menampilkan angka global.
- Blade + HardeningTest: mahasiswa melihat 1.300.000 miliknya.
## 2026-11-13 (ronde 19) — agregat performa 2N menjadi 1 query
- Approval-rate KRS per TA: GROUP BY + SUM kondisional (DB-agnostic, binding status).
## 2026-11-14 — prasyarat ganda dicek validasi KRS
- AkademikService::validasiKrs kini memeriksa relasi mk_prasyarat (jenis=prasyarat) selain kolom legacy; PrasyaratTest (2 tes).
## 2026-11-14 — scope bimbingan dosen di dashboard
- KRS menunggu untuk dosen dihitung khusus mahasiswanya (selaras PersetujuanPa), bukan global; HardeningTest +1.
## 2026-11-14 — wisuda anti-overbook + guard teruji
- Yudisium::daftarkanWisuda dalam transaksi + lock batch (cek status & kuota ulang di dalam); KelulusanMbkmTest +1 (kuota penuh & batch tutup ditolak).
## 2026-11-14 — cache agregat ditolak dengan bukti (bukan asumsi)
- Investigasi: DatabaseStore + config serializable_classes=false menolak unserialize objek Eloquent (anti object-injection) — cache angka landing sebagai objek akan rusak saat dibaca.
- Keputusan: agregat dihitung langsung (bounded + index); CacheStoreTest mengunci perilaku aman default framework.
## 2026-11-14 — verifikasi pre-deploy cache
- route:cache + view:cache + config:cache sukses; / & /dashboard lolos dengan cache aktif; optimize:clear setelahnya.
## 2026-11-14 (UI overhaul, BELUM COMMIT) — shell + dashboard + landing baru
- Shell: sidebar terang + collapse persisten + drawer mobile, topbar (breadcrumb, pencarian /cari nyata, badge periode, lonceng notifikasi real, menu akun), footer universal.
- Dashboard: komposisi 8 blok (hero sapaan peran, 6 metrik, strip institusi, growth, donat, arus kas, funnel PMB real, performa, pengumuman, 3 antrean scoped, aktivitas, aksi cepat).
- Landing 11 seksi + mockup HTML/CSS berdata real; pencarian global + composer notifikasi/periode; bayar/KRS diseragamkan; 200/200 tes hijau.
## 2026-11-14 (lanjutan) — konsistensi modul generik + form PMB
- pages/livewire-page: kartu kampius + breadcrumb 3 tingkat → berlaku untuk SEMUA halaman Livewire (keuangan, admin, kalender, helpdesk, profil, AI, dsb).
- PMB publik: tombol submit dengan loading anti double-submit.
## 2026-11-14 (mega command) — neraca saldo + linimasa + dokumen bukti
- Keuangan: ekspor neraca saldo per akun (guard pimpinan, teruji 2 arah + 403).
- Profil mahasiswa: linimasa siklus (akun→KRS→tagihan→bayar), owner-scope, teruji ada/tidak.
- RouteGuardTest mencakup neraca + pencarian + referensi nama rute di semua Blade; skor mega 79/100 terdokumentasi jujur.
- Docs: MODULE/ FEATURE_MATRIX, SECURITY_AUDIT, PERFORMANCE_AUDIT, TESTING_REPORT, ROADMAP, API_DOCUMENTATION, INTEGRATION_GUIDE, DEPLOYMENT_GUIDE, BACKUP_RESTORE, DEVELOPMENT_MASTER_1130 (+BACKLOG/PRIORITY/SCORE/DEPENDENCIES), MASTER_ARCHITECTURE; FINAL_SCORECARD ditulis ulang per rubrik mega (79/100).
- Halaman error 403/404/500 bermerek + ErrorPageTest; timezone Asia/Jakarta (suite tetap hijau).
## 2026-11-14 — konsistensi tombol alur KRS
- 4 tombol tanpa kelas Tabler (ajukan, keluar waitlist, setujui/tolak PA) kini ber-style + loading; KrsFlowTest tetap hijau.
## 2026-11-14 — hardening konfigurasi produksi
- .env.example: panduan produksi (SECURE_COOKIE, APP_DEBUG=false); DEPLOYMENT.md: checklist env + scheduler.
- SecurityConfigTest: default cookie aman + throttle login teruji.
## 2026-11-14 — konsistensi visual + pencarian denda
- Dashboard pimpinan memakai token kampius-stat/kampius-card (radius 16px, tipografi navy) selaras dashboard utama.
- DendaRuleKelola: kolom pencarian jenis + reset halaman.
## 2026-11-13 (ronde 20) — proteksi hapus akun sendiri di data dosen/tendik
- DosenData::hapus meniru UserData: tolak hapus diri sendiri (tendik & dosen), cegah lockout admin.
- DosenDataTest +1: hapus diri ditolak + akun tetap ada.
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
