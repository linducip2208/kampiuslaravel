# PERFORMANCE_AUDIT (pengukuran nyata, 2026-11-14)

## Benchmark terukur
- Dashboard superadmin (DB kosong, sqlite): **93 query**, ambang regresi **100**
  (`DashboardPerfTest`). Komposisi: ~77 agregat guarded + 16 shell (notif + periode + menu/role).
- Suite penuh: 204 tes / ~21–40 dtk (mesin dev Windows, sqlite in-memory).
- Index live terverifikasi di MySQL 8.4: pembayaran(status), pembayaran(verified_at),
  pembayaran(status,verified_at), krs(status), krs(tahun_akademik_id,status),
  mahasiswa(status), pmb_pendaftars(status), tagihan(status), tagihan(mahasiswa_id,status).

## Perbaikan diterapkan
1. N+1 → eager/batch (6 titik): InputNilai preload lock, Tagihan API +3 relasi,
   VerifikasiBayar nested +2, PersetujuanPa nested +3, Pengajuan waitlist eager, ringkasan().
2. 2N → 1 query: approval-rate KRS per TA (GROUP BY + SUM kondisional, DB-agnostic).
3. Chunk 500 untuk semua ekspor CSV (tanpa unbounded get).
4. Paginasi 10–20 di semua list; paginate pageName terpisah untuk multi-tabel.

## Keputusan sadar (dengan bukti, bukan tebakan)
- Cache agregat objek DITOLAK: `cache.serializable_classes=false` membuat unserialize
  Eloquent gagal (`__PHP_Incomplete_Class`), dibuktikan CacheStoreTest. Strategi ganti:
  agregat 1-query + index + ambang regresi.
- Cache agregat skalar publik belum dipasang: risiko stale angka keuangan > manfaat.
  Cache hanya aman untuk: daftar statis (prodi dropdown) — belum dibutuhkan.

## Sisa (lihat ROADMAP)
Benchmark data besar (seed 10k+), slow-query log produksi, rehearsal dengan
`config:cache + route:cache` di staging (sudah lolos lokal), monitoring APM.
