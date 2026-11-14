# MODULE INVENTORY — KAMPIUS (dari source aktual, 2026-11-14)

Sumber: routes/web.php, routes/api.php, routes/console.php, app/Models (82),
app/Http/Controllers (35), app/Livewire (44 komponen), resources/views (~80).

| Domain | Implementasi | Rute kunci |
|---|---|---|
| Auth & sesi | SessionAuthController, throttle login, regenerate/rotate | login, logout |
| Dashboard | DashboardController (global + pimpinan, guarded, role-scope) | dashboard, dashboard.pimpinan |
| Pencarian | PencarianController (katalog publik, min 2 char, escape LIKE) | pencarian |
| PMB | PmbController (trx+lock kuota), DaftarPublik, Verifikasi (trx+lock), funnel | pmb.create/store/cek, admin.pmb.verifikasi, pmb.funnel |
| Mahasiswa | Mahasiswa CRUD via ImporCsv + admin, KRS Pengajuan (trx+lock+kuota), cuti | mahasiswa.krs.pengajuan, kemahasiswaan.cuti |
| Dosen/Wali | DosenData, WaliList, PersetujuanPa (scope bimbingan) | admin.dosen, dosen.wali, dosen.krs.persetujuan |
| Akademik | Kurikulum + relasi prasyarat/ekivalensi, TahunAkademik, Kelas, KHS/KTM/Transkrip + TTE QR | admin.kurikulum, khs.show/verify, transkrip.cetak/verify, ktm.cetak/verify |
| Nilai | InputNilai (ownership+trx+lock), KunciNilai (buka admin-only), remedial D/E≤55, NilaiSidang (trx) | nilai.input, nilai.kunci, sidang.nilai |
| Keuangan | TagihanMassal (trx+race-safe), BayarManual (dedup+trx), VerifikasiBayar, DendaRuleKelola, denda:terapkan harian, refund+jurnal, rekonsiliasi CSV | keuangan.tagihan/verifikasi/beasiswa/denda/bayar, keuangan.refund |
| Beasiswa | Kelola + assign + potongan otomatis (clamp) | keuangan.beasiswa |
| Skripsi/Sidang | PengajuanJudul, BankTopik, SetPembimbing, Bimbingan Log, Pendaftaran, NilaiSidang, Yudisium (trx+lock kuota, SKL+alumni) | skripsi.*, bimbingan.log, sidang.* |
| Surat | Pengajuan, NomorOtomatis (confirm), verify QR publik | surat.pengajuan/nomor/verify/cetak, kwitansi.cetak |
| Kalender | Kelola + ICS + locks (kalender tutup) | kalender.index/.ics |
| Helpdesk | Tiket + balasan | helpdesk.index |
| Pengumuman | Publik CRUD via admin + broadcast notifikasi | publik.pengumuman*, notifikasi.broadcast |
| LMS | MateriCrud, TugasCrud, KumpulTugas (anti-spoof), absensi QR | lms.*, absensi.scan |
| Analitik/AI | AtRisk, Chatbot, Media Generator (ComfyUI) | analitik.at-risk, ai.chat, media.generate |
| Laporan | 7 ekspor CSV + rekap + neraca saldo | laporan.*, admin.impor(+template) |
| PDDikti | Index + export/push/sync/sinta/garuda/tte (perlu kredensial) | pddikti.* |
| Integrasi | Admin Integrasi (driver payment, webhook secret, FCM) | admin.integrasi |
| Audit | AuditLog viewer + scheduler audit | admin.audit |
| API v1 | Sanctum abilities:mobile — auth, jadwal, krs, nilai, absensi, tugas, tagihan+VA, bimbingan, pengumuman, notifikasi, ai, device-token | /api/v1/* |
| API v2 | Publik read-only throttle + webhook HMAC | /api/v2/* |
| Scheduler | tagihan:ingatkan 08:00, denda:terapkan 01:00 (withoutOverlapping) | console.php |
| Profil | Profil (nama/email/password, linimasa mahasiswa) | profil |

Tidak ada: mesin kuis/bank soal, OBE/CPL-CPMK, riset & pengmas, GL penuh (CoA/tutup periode/budget), SSO, gateway production, aplikasi mobile.
Lihat FEATURE_MATRIX untuk status tiap modul dan ROADMAP untuk rencana.
