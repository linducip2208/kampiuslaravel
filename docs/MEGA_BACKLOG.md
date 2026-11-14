# MEGA BACKLOG — kerja nyata, bukan angka fabrikasi

Keputusan tercatat (MEGA_AUDIT K2): target "1.500 item" DITOLAK_WITH_EVIDENCE.
Alasan: 1.135 vs 831 dokumen lama saja tidak bisa direkonsiliasi dari kode;
menulis 1.500 baris berarti ±1.400 klaim tanpa kode pendukung — melanggar aturan
"tidak mengutamakan jumlah di atas kebenaran". Backlog ini berisi **66 item**
yang masing-masing menunjuk ke file+baris. Item baru lahir dari Wave (bukan dari
target angka), dan file ini ditambah saat itu terjadi.

Format: `ID | modul | prioritas | kompleksitas | wave | kriteria terima + tes`.

## WAVE A — fondasi kritis (20 item, urut dependensi)

- M-001 keuangan P0/M A: tolak callback `PMBVA-` bila signature null
  (`BayarController:94-96`). Terima: callback tanpa signature → 422 + tidak lunas.
  Tes: replay paralel 2x → 1 pelunasan.
- M-002 keuangan P0/M A: tx+lock `PmbVaService:56-68`. Tes: double-callback → 1 settle.
- M-003 keuangan P0/S A: lock baris tagihan di `BayarManual:58-75`. Tes: 2 submit → 1 pending.
- M-004 keuangan P0/S A: cegah 2 VA pending (`PaymentService:111-119`). Tes: klik ganda → 1 VA.
- M-005 keamanan P0/S A: allowlist `act` di `PddiktiController:38-42`. Tes: act liar → 422.
- M-006 keamanan P1/S A: `AuditLog` tiap export PDDikti. Tes: export → 1 baris audit milik user.
- M-007 akademik P0/S A: `KhsController:34` — IPS dari `per_tahun`, bukan IPK global.
  Tes: IPS sem1 ≠ IPK global pada data 2 semester.
- M-008 akademik P0/M A: IPK server-side di KRS (hapus input manual
  `Pengajuan:42,188,253` + Blade `:14-15`). Tes: snapshot dikirim manual diabaikan.
- M-009 akademik P1/S A: `WaliList:67-70` pakai `AkademikService::ringkasan` (bobot SKS).
  Tes: IPK wali == IPK transkrip.
- M-010 akademik P1/M A: `ringkasan()`/KHS/Transkrip hormati `is_locked`. Tes: nilai
  terkunci tidak berubah di publikasi; edit ditolak (sudah ada).
- M-011 keuangan P1/S A: samakan definisi penerimaan (`DashboardController:46` vs `:276`).
  Tes: satu sumber angka denda.
- M-012 keuangan P1/S A: assertion balance jurnal (debet=kredit) di service.
  Tes: jurnal timpang → exception + rollback.
- M-013 DB P1/S A: index `pembayaran(tagihan_id,status)` + `va_reference` unik.
- M-014 DB P1/S A: SoftDeletes `tagihan_cicilan` + tes restore.
- M-015 keamanan P1/M A: Policy per model (Mahasiswa, Nilai, Krs, Surat, Pembayaran,
  Tagihan) + migrasi route ke `can:`; trait manual jadi basis. Tes IDOR web+API.
- M-016 keamanan P1/S A: permission Spatie dipakai (ganti 1 grup route dulu sebagai pola).
- M-017 keamanan P2/S A: idempotency-key + event-log webhook. Tes: replay → 200 tanpa efek ganda.
- M-018 keuangan P2/S A: enforce `kuota` beasiswa (atau hapus kolom via migrasi). Tes: kuota habis → ditolak.
- M-019 tes P1/S A: pecah duplikat `ExampleTest` (Unit rename).
- M-020 tes P1/S A: tes traversal upload per modul (pola `DokumenTraversalTest`).

## WAVE B — lengkapi modul berjalan (28 item, ringkas)

- M-021..M-026 akademik: auto-rollover periode + partial-unique 1 aktif (P1/M);
  scheduler akademik (P2/S); workflow DO/keluar otomatis (P2/L); paginasi KHS/Transkrip
  lintas (P2/M); optimasi P1–P4 performance report (P1/M); rekomendasi MK+waitlist
  (`KelasWaitlist` sudah ada modelnya) (P2/L).
- M-027..M-031 LMS: tes Tugas+Materi (P1/S); mesin kuis di atas BankSoal —
  Coronis vertikal: Attempt + autosave + nilai otomatis PG (P0/XL, Wave C);
  forum (P2/L); SCORM/offline (P3/L).
- M-032..M-037 keuangan: tutup-buku periode + buka-terkendali (P1/L); anggaran vs
  realisasi (P2/L); AP/AR (P2/L); rekon bank (P2/M); aset akuntansi (P3/M);
  ekspor chunked + otorisasi ekspor (P1/M).
- M-038..M-041 HR: payroll (P2/XL); rekrutmen/onboarding (P3/L); organisasi/UKM (P3/M);
  reservasi lab/fasilitas (P2/M, model `PeminjamanRuang` sudah ada).
- M-042..M-044 riset: hibah+anggaran (P2/L); publikasi/HKI (P3/M); pengabdian (P2/M).
- M-045..M-048 platform: envelope+versioning API konsisten + OpenAPI (P1/M);
  rate-limit endpoint sensitif (P1/S); backup/restore teruji (P0/M);
  observability/health (P1/S).

## WAVE C — modul enterprise baru (12 item, vertikal penuh)

- M-049 OBE/CPL/CPMK + pemetaan asesmen (P1/XL). M-050 akreditasi evidence (P1/L).
- M-051 CBT session: timer, autosave, recovery (P0/XL, di atas M-027+ mesin kuis).
- M-052 GL akuntansi penuh (P1/XL). M-053 BKD lanjut (P2/L).
- M-054 multi-kampus: DESAIN DULU (isolasi, permission, migrasi) — tanpa desain
  yang disahkan status BLOCKED (P0/XL).
- M-055 alumni+karier (P2/M, tracer sudah ada). M-056 analitik eksekutif+KPI (P2/L).
- M-057 RAG knowledge-base + guardrail AI (P1/XL). M-058 kuota/biaya AI (P1/M).
- M-059 kontrak mobile Flutter per dokumen Fase 6 (P1/L).
- M-060 PWA portal mahasiswa (P2/L).

## WAVE D — integrasi (6 item, semua BLOCKED sampai konektivitas nyata diuji)

- M-061 PDDikti/Feeder adapter (BLOCKED). M-062 payment gateway prod (BLOCKED).
- M-063 WhatsApp (BLOCKED). M-064 Email/FCM (PARTIAL — FCM token ada, lanjutkan).
- M-065 SSO Google/Microsoft (BLOCKED). M-066 tanda-tangan dokumen (BLOCKED).

## Status global

TOTAL=66: WAVE-A=20, WAVE-B=28, WAVE-C=12, WAVE-D=6. Selesai=0 (runde belum mulai).
Setiap item selesai → pindah ke IMPLEMENTATION_LOG + tes hijau + bukti.
