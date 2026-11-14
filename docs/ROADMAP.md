# ROADMAP (jujur: belum dikerjakan, jangan diklaim sudah ada)

## Prioritas 1 — butuh keputusan/kredensial pemilik
- [ ] Mesin kuis & bank soal + ujian online (butuh desain penilaian; estimasi besar).
- [ ] OBE: CPL/CPMK, pemetaan MK, evidensi asesmen, rekap akreditasi (jangan klaim sertifikasi).
- [ ] Modul riset & pengabdian (proposal, dana, luaran, HKI).
- [ ] GL penuh: Chart of Accounts, jurnal umum manual, tutup periode + pembukaan ulang terkontrol, budget.
- [ ] SSO (Google/Microsoft) + gateway production + repo Flutter (mahasiswa/dosen/orang tua).
- [ ] Multi-kampus/tenant (arsitektur siap parsial: Fakultas→Prodi; butuh desain scope kampus).

## Prioritas 2 — peningkatan aman
- [ ] Unsaved-change guard pada form panjang.
- [ ] Notifikasi actionable penuh (tiket helpdesk, deadline KRS) + pusat notifikasi.
- [ ] Ekspor Excel (saat ini CSV `;`).
- [ ] PSEO lanjutan (sitemap dinamis, JSON-LD per prodi sudah ada).

## Prioritas 3 — verifikasi manual (butuh manusia/browser)
- [ ] Screenshot 12 tampilan ke docs/screenshots/.
- [ ] Uji 9 breakpoint + keyboard/kontras.
- [ ] Uji restore backup triwulanan.
- [ ] Review visual modul-per-modul sisa.

## Ditolak sadar (dengan alasan tercatat)
- Cache agregat objek (merusak unserialize; lihat PERFORMANCE.md).
- Harmonisasi FK restrict massal (risiko lockout hapus data).
- migrate:fresh/db:wipe di environment berisi data.
