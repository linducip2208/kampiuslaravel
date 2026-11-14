# PERFORMANCE
- InputNilai: 2N query -> 2 query (preload lock) + 1 transaction.
- Tagihan API: +3 eager; VerifikasiBayar: +2 nested; PersetujuanPa: +3 nested.
- Dashboard: semua agregat guarded + paginate 10 (KRS/sidang/keuangan).
- Index: 9 index status/komposit terverifikasi live di MySQL 8.4 (cek Schema::getIndexes pembayaran: status, verified_at, komposit ada).
- Benchmark teruji: DashboardPerfTest — dashboard superadmin DB kosong = 93 query (77 agregat + 14 shell/topbar), ambang regresi 100 (sqlite, 2026-11-14).
- Optimasi: approval-rate KRS per TA dari 2N query menjadi 1 agregat (GROUP BY + SUM kondisional); chart 6 bulan tetap 12 agregat index (bounded).
- Cache agregat DITOLAK sadar: default framework (cache.serializable_classes=false) menolak unserialize objek Eloquent (anti object-injection, teruji di CacheStoreTest); melemahkannya demi angka marketing adalah trade-off yang salah. Strategi: query bounded + index.
