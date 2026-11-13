# PERFORMANCE
- InputNilai: 2N query -> 2 query (preload lock) + 1 transaction.
- Tagihan API: +3 eager; VerifikasiBayar: +2 nested; PersetujuanPa: +3 nested.
- Dashboard: semua agregat guarded + paginate 10 (KRS/sidang/keuangan).
- Index: 9 index status/komposit terverifikasi live di MySQL 8.4 (cek Schema::getIndexes pembayaran: status, verified_at, komposit ada).
- Benchmark teruji: DashboardPerfTest — dashboard superadmin DB kosong = 79 query, ambang regresi 90 (sqlite, 2026-11-13).
- Belum: cache agregat 60s (risiko stale verifikasi pending — ditunda sadar), benchmark data besar, browser test.
