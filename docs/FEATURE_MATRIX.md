# FEATURE MATRIX

Legenda: NOT_STARTED / PARTIAL / IMPLEMENTED / TESTED / VERIFIED.
VERIFIED = implementasi + rute + validasi + otorisasi + DB + UI + tes hijau.
(Browser manual hanya oleh pemilik, tanpa screenshot terarsip.)

| Fitur | Status | Bukti |
|---|---|---|
| Login/logout + throttle + regenerasi sesi | VERIFIED | SessionAuthController, SecurityConfigTest |
| RBAC 6 role + permission | VERIFIED | RolesPermissionsSeeder, SecurityTest, RouteGuardTest (60 asersi) |
| Dashboard global + pimpinan, role-scope | VERIFIED | DashboardController, 197→204 tes hijau |
| Pencarian katalog global | VERIFIED | PencarianController, PencarianTest (3) |
| PMB daftar + kuota lock | VERIFIED | PmbController, DaftarPublik, PmbFlowTest |
| PMB verifikasi + kelulusan lock | VERIFIED | Pmb/Verifikasi, PmbFlowTest |
| Funnel PMB | VERIFIED | PmbFunnelController + dashboard funnel |
| KRS pengajuan + revisi-ditolak | VERIFIED | Krs/Pengajuan, KrsFlowTest (+revisi) |
| KRS persetujuan PA scoped | VERIFIED | PersetujuanPa, KrsFlowTest |
| Prasyarat tunggal + relasi | VERIFIED | AkademikService, PrasyaratTest (2) |
| KHS/KTM/Transkrip + TTE QR | VERIFIED | CetakDokumenTest |
| Nilai input/kunci/remedial | VERIFIED | InputNilai (ownership+trx), NilaiTest |
| Sidang + yudisium + wisuda | VERIFIED | NilaiSidang, Yudisium (trx+lock), KelulusanMbkmTest |
| Tagihan massal + denda aturan + auto | VERIFIED | TagihanMassal, DendaRuleKelola, DendaOtomatisTest |
| Bayar VA/QRIS/ewallet + retry 3x | VERIFIED | PaymentService, BayarController, KeuanganTest |
| Verifikasi/refund+jurnal | VERIFIED | KeuanganService (trx+lock+idempoten), KeuanganPhase2Test |
| Rekonsiliasi CSV | VERIFIED | KeuanganPhase2Test |
| Neraca saldo ekspor | VERIFIED | LaporanController::exportNeraca, NeracaTest (2) |
| Beasiswa + potongan | VERIFIED | Beasiswa/Kelola, CalculationTest |
| Surat + nomor + verify QR | VERIFIED | SuratController, OperasionalBaruTest |
| Kalender + ICS + locks | VERIFIED | KalenderLockTest |
| Helpdesk tiket | VERIFIED | Tiket + balasan (FiturBaruTest) |
| Pengumuman + broadcast | VERIFIED | Broadcast, notifikasi bell |
| LMS materi/tugas/kumpul | VERIFIED | KumpulTugas anti-spoof, TugasController scope |
| Absensi QR + rekap <75% | VERIFIED | AbsensiTest, rekapAbsensi |
| At-Risk + chatbot + generator | TESTED | Analitik/AI ada; AI butuh kredensial provider (tanpa itu = saran statis) |
| Laporan 8 ekspor | VERIFIED | Export CSV per modul + neraca |
| PDDikti mapping | PARTIAL | Adapter + rute ada; butuh kredensial Feeder (jangan klaim sync sukses) |
| API v1 mobile scope | VERIFIED | MobileApiTest, ApiGuardTest |
| API v2 + webhook HMAC | VERIFIED | IntegrasiTest (HMAC 503/400/ok) |
| Soft delete keuangan + index | VERIFIED | Migrasi aman + HardeningTest + DashboardPerfTest (93<100) |
| Health + migrasi guard | VERIFIED | HealthTest |
| Route guard regression | VERIFIED | RouteGuardTest |
| Linimasa mahasiswa | VERIFIED | Profil::linimasaMahasiswa, UserDataTest (+2) |
| KRS bimbingan di dashboard | VERIFIED | HardeningTest (nama, bukan angka) |
| Bank soal PG/esai (CRUD + validasi) | VERIFIED | Lms/BankSoal, BankSoalTest (5); mesin ujian tetap NOT_STARTED |
| Mesin kuis / ujian online | NOT_STARTED | Lihat ROADMAP (butuh assignment engine di atas bank soal) |
| OBE CPL/CPMK | NOT_STARTED | Lihat ROADMAP (jangan klaim akreditasi) |
| Riset & pengmas | NOT_STARTED | Lihat ROADMAP |
| GL penuh (CoA/tutup periode) | PARTIAL | Jurnal + neraca ada; tutup periode terkontrol belum ada |
| SSO / gateway prod / mobile app | NOT_STARTED | Butuh kredensial & repo Flutter |
| Cache agregat | DITOLAK SADAR | Buktikan merusak (serializable_classes=false); strategi = agregat 1 query + index |
