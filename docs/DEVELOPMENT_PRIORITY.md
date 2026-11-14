# DEVELOPMENT PRIORITY (urutan eksekusi)

## Fase 0 — Fondasi (selesai, teruji)
Timezone Asia/Jakarta, guard middleware, benchmark <100 query, health guard.
Suite: 204/204 hijau. Jangan regresi: jalankan suite tiap ronde.

## Fase 1 — P0 (keamanan, integritas finansial/akademik, anti-bocor)
1. A2-060 rollback daftar ulang aman (tes dengan data terisolasi).
2. A1-045 DO + audit; A3-020 arsip KRS immutable.
3. A8-059/060/080-082 guardrail AI (redaksi PII, scope, larangan).
4. A1-085 legalisir tercatat; A3-043 transkrip unofficial (bedakan resmi vs draft).
5. A10-061 FK harmonisasi — HANYA dengan persetujuan eksplisit + backup (risiko lockout hapus).

## Fase 2 — P1 (fitur inti bernilai)
1. A4 bank soal → kuis → ujian (modul baru terbesar; fondasi A4-014 s.d. A4-031).
2. A7 riset + pengmas (modul baru; fondasi A7-029 s.d. A7-048).
3. A5 GL: CoA → jurnal umum → tutup buku (A5-054 → A5-057).
4. A8 RAG + 7 asisten bertahap (mulai A8-008/009 + A8-011).
5. A9: error state kustom + pusat notifikasi + pencarian diperluas (owner-scope).
6. A10: security headers + API filter allowlist + throttle ekspor.

## Fase 3 — P2 (otomasi, analitik, integrasi)
Retry webhook keluar, template surat, kanban tiket, dashboard AR aging,
prediksi kelulusan (non-AI dulu), rekap akreditasi, PSEO/sitemap.

## Fase 4 — P3 (penyempurnaan)
Mode gelap, i18n, PWA, gamifikasi mendalam, A/B testing, embel-embel sosial.
Kerjakan hanya bila Fase 1–2 hijau dan ada pemilik keputusan.

## Aturan setiap ronde
Satu slice vertikal (DB→UI→tes), pint bersih, suite hijau, docs diperbarui,
tanpa commit/push tanpa perintah eksplisit.
