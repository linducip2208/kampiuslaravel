# MEGA DEPENDENCIES — urutan gelombang

```
Wave A (fondasi) ─┬─▶ Wave B (modul berjalan) ─┬─▶ Wave C (enterprise) ─▶ Wave D (integrasi)
                  │                             │
                  ├─ M-007/008/009/010 ─▶ IPS & IPK benar ─▶ M-021 (waitlist/rekomendasi)
                  ├─ M-015/M-016 (Policy) ─▶ semua modul B/C memakai can:
                  ├─ M-012/M-013/M-014 (jurnal+index) ─▶ M-032..037 (tutup buku, AP/AR)
                  ├─ M-001..004/M-017 (callback) ─▶ M-062 gateway prod (D)
                  └─ M-020/M-019 (tes) ─▶ standar tes semua wave
Wave C internal: mesin kuis (M-027) ─▶ CBT (M-051) ─▶ RAG ujian? tidak — terpisah.
M-054 multi-kampus BLOCKED tanpa desain isolasi yang disahkan.
```

Aturan: satu migrasi/rute/layout/service/shared-component hanya disentuh 1 agen
dalam satu waktu (pemilik tunggal per file per gelombang). Rekonsiliasi migrasi
sebelum merge; tes tiap merge wave; tidak ada `migrate:fresh`/`db:wipe`.
