# DEVELOPMENT BACKLOG (turunan MASTER, 2026-11-14)

Sumber kebenaran: docs/DEVELOPMENT_MASTER_1130.md (1.135 item unik, 0 duplikat).
Backlog = semua item NOT_STARTED (761) + PARTIAL (70) = **831 item**.
VERIFIED (295) + IMPLEMENTED (8) + DITOLAK-berdasar-bukti (1) bukan backlog.

## Distribusi backlog per agen (NOT_STARTED + PARTIAL)
| Agen | Backlog | Fokus terbesar |
|---|---|---|
| A1 Akademik | 69 | OBE/CPL-CPMK, akreditasi, mutasi, arsip |
| A2 PMB/lifecycle | 80 | CRM/lead, seleksi lanjutan, mutasi/cuti detail |
| A3 KRS/KHS | 62 | Revisi lanjutan, rekomendasi, transkrip OTA |
| A4 LMS | 114 | Bank soal/ujian, forum, gamifikasi, offline |
| A5 Keuangan | 83 | GL/CoA/tutup buku/budget, AP/AR, aset |
| A6 HR/operasi | 105 | Penggajian, aset, inventaris, org, UKM |
| A7 Skripsi/riset | 84 | Riset+pengmas+HKI+publikasi (modul baru) |
| A8 AI | 108 | RAG, 7 asisten, guardrail, evaluasi |
| A9 UI/UX | 62 | Tabs/bulk/sort, kanban, PWA, i18n, screenshot |
| A10 Infra | 62 | MFA, WAF, SSO, backup drill, API paging/filter |

## P0 dalam backlog (wajib didahulukan bila dikerjakan)
- A1-045 DO + audit; A1-060 rollback daftar ulang; A1-085 legalisir; A2-060 rollback; A2-104 hapus data pendaftar; A3-020 arsip KRS; A8-059 redaksi PII; A8-060 scope RAG; A8-080/081/082 larangan AI; A10-061 FK harmonisasi (**DITAHAN: risiko lockout hapus**).
- Aturan: P0 keamanan/integritas/data dikerjakan dulu, dengan tes + tanpa migrate destruktif.

## Cara pakai
Pilih item → baca baris master (kolom acceptance + tes) → implementasi → ubah status di master → tambah tes → jalankan suite.
