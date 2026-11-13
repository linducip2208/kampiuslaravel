# FINANCIAL_WORKFLOWS (real)
- Tagihan massal: transaction + skip duplikat + tangkap race QueryException.
- VA: reuse pending kanal sama; tolak bila lunas (422); callback tolak nominal mismatch + simulated di prod.
- Manual: dedup pending + transaction + hapus file bila DB gagal.
- Verifikasi: transaction+lock, idempotent (pending saja), jurnal penerimaan firstOrCreate.
- Refund: hanya verified & belum refund; kurangi terbayar + jurnal reversal.
- Rekonsiliasi CSV: cocok nominal persis; selisih/tak dikenal dilaporkan.
- Retensi: tagihan/pembayaran/jurnal pakai SoftDeletes (migrasi 2026_11_13_145235) — hapus = arsip, audit tetap.
