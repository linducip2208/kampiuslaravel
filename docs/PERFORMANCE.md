# PERFORMANCE
- InputNilai: 2N query -> 2 query (preload lock) + 1 transaction.
- Tagihan API: +3 eager; VerifikasiBayar: +2 nested; PersetujuanPa: +3 nested.
- Dashboard: semua agregat guarded + paginate 10 (KRS/sidang/keuangan).
- Belum diukur: benchmark before/after ms; cache laporan belum ada (invalidasi berisiko) — disarankan cache 60s untuk tren + rekap.
