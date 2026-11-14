# MEGA FEATURE INVENTORY — status per modul (bukti file)

Legenda: VERIFIED = implementasi + DB + validasi + otorisasi + UI benar + tes hijau.
PARTIAL = jalan tapi ada lubang dari kolom bukti. NOT_STARTED = tidak ada kode.

## Akademik inti — PARTIAL (hitungan kuat, 3 bug)

| Fitur | Status | Bukti |
|---|---|---|
| IPK globaI (`AkademikService:25-60`) | VERIFIED | CalculationTest + KrsFlowTest hijau |
| Batas SKS by IPK (`:63-70`, `Krs:92-95`) | PARTIAL | input IPK manual (`Pengajuan:42,188,253`) bisa akali |
| Validasi KRS terpusat (`:168-275` + re-validasi tx) | VERIFIED | PrasyaratTest + KrsFlowTest |
| KHS/Transkrip + QR signed (`DokumenTte`) | VERIFIED | CetakDokumenTest |
| IPS label (`KhsController:34`) | BUG→PARTIAL | IPS = IPK global (MEGA_AUDIT F5) |
| IPK WaliList `AVG(bobot)` | BUG→PARTIAL | tanpa bobot SKS (F6) |
| Kalender manual + lock jenis | VERIFIED | KalenderLockTest |
| Auto-rollover periode / 1-aktif unik | NOT_STARTED | tidak ditemukan di kode |
| Cuti + ubah status + yudisium→alumni | VERIFIED | KemahasiswaanCutiTest + KelulusanMbkmTest |
| Workflow DO/keluar otomatis | NOT_STARTED | hanya `ubahStatus` manual |

## PMB & lifecycle — PARTIAL

Daftar publik + verifikasi + gelombang + VA (`PmbVaService`) + referral/marketing:
PmbFlowTest + PmbMarketingTest hijau. Lubang: settle PMB tanpa signature (S1),
tanpa lock (S2), tanpa auto-rollover gelombang. Status mahasiswa lain manual.

## KRS/KHS/Transkrip — PARTIAL (lihat Akademik)

Alur pengajuan→persetujuan PA terkunci (`lockForUpdate`, revisi diblokir) VERIFIED.
Jadwal: generate + deteksi bentrok ada (`JadwalServiceTest`) tapi bentrok pakai
`get()->contains()` in-memori (unbounded) → optimasi Wave B.

## LMS & ujian

| Fitur | Status | Bukti |
|---|---|---|
| BankSoal CRUD (PG/esai, kunci, bobot) | VERIFIED | BankSoalTest (5 tes) |
| Mesin kuis/ujian (Quiz yatim) | NOT_STARTED | tanpa route/controller/Livewire/relasi |
| Tugas + kumpul (anti-spoof) | PARTIAL | tanpa tes khusus |
| Materi | PARTIAL | tanpa tes khusus |
| Forum/diskusi | NOT_STARTED | nol kode |

## Keuangan — PARTIAL (fondasi kuat, 5 lubang)

`KeuanganService` tx+lock, jurnal single-row double-entry, denda/beasiswa di model,
rekon CSV, VA multi-adapter, neraca: KeuanganTest + KeuanganPhase2Test +
DendaOtomatisTest + DendaRuleTest + NeracaTest hijau. Lubang: S1–S4, S6, F11
(dual angka denda), `TagihanCicilan` tanpa SoftDeletes, `kuota` beasiswa tak dipakai,
tanpa assertion balance jurnal.

## HR & operasional — PARTIAL

BKD/beban (`BebanDosen`), jurnal dosen, presensi, aset+maintenance, pinjam ruang,
helpdesk+tiket+SLA, cuti dosen, SK mengajar: OperasionalBaruTest hijau.
Lubang: tanpa payroll/kompensasi, tanpa rekrutmen/onboarding (NOT_STARTED);
organisasi/kepanitiaan & UKM minimal.

## Riset & kelulusan — PARTIAL

Skripsi (topik, judul, plagiarisme, pembimbing, bimbingan berversioning, sidang+
penguji+nilai, yudisium): SkripsiFlowTest hijau. Wisuda batch+peserta, alumni+
tracer, magang, MBKM (mitra/logbook/konversi): KelulusanMbkmTest hijau.
Lubang: hibah/batch anggaran, publikasi/HKI, pengabdian (NOT_STARTED atau minimal).

## AI & analitik — PARTIAL

Chatbot + `AiJob`, materi-AI (`buatQuiz` generator, bukan engine), AtRisk,
broadcast/notifikasi + FCM/device-token: AiFeatureTest + IntegrasiTest hijau.
Lubang: tanpa RAG knowledge-base, tanpa guardrail/evaluasi prompt, tanpa kuota/biaya
(Model `AiJob` ada — cakupan perlu diverifikasi Wave B).

## UI/UX — PARTIAL (rinci di MEGA_UI_UX_REPORT)

Landing 11 seksi + foto WAN 2.1, login, shell Tabler terang, dashboard 8 blok,
error pages, pencarian global: RouteGuardTest + PencarianTest hijau.
Lubang: tanpa E2E/screenshot, tanpa PWA, aksesibilitas keyboard belum diaudit.

## Platform & API — PARTIAL

Sanctum mobile (`abilities:mobile`), API v1 (12 controller) + v2 publik,
throttle, audit log, health: ApiAuthTest + ApiGuardTest + MobileApiTest + HealthTest.
Lubang: tanpa versioning envelope konsisten, tanpa OpenAPI, tanpa rate-limit
per-endpoint sensitif, scheduler hanya 2 job keuangan, tanpa backup/restore
teruji (`BACKUP_RESTORE.md` dokumen saja).
