# Runbook Uji Restore Terjadwal (triwulanan, manual)

> Ide bank "Uji restore terjadwal": backup teruji (`backup:create` +
> `backup:verify`), restore belum pernah dicoba. Dokumen ini = runbook
> + checklist + jadwal. Eksekusi manual di staging; catat bukti tiap
> triwulan di bawah.

## Prasyarat

- File backup terbaru dari `storage/app/backup/` (manifest + gzip per tabel).
- Database staging kosong + kode rilis yang sama (cek `git rev-parse HEAD`).
- `APP_ENV=staging`, kredensial DB staging di `.env.staging`.

## Langkah

1. Salin arsip backup triwulan ke staging.
2. Gunzip tiap tabel: `gunzip -c <tabel>.json.gz | head -c 500` (pastikan
   JSON valid + jumlah baris = manifest).
3. Restore per tabel via `mysql`/seeder darurat sesuai urutan FK
   (master → akademik → keuangan). Catat perintah persis yang dipakai.
4. Verifikasi:
   - `php artisan migrate:status` → 0 pending (atau hanya migrasi baru).
   - `php artisan permission:review` → exit 0.
   - Login + buka `/health` → `ok: true`.
   - Spot-check: 1 KRS, 1 tagihan, 1 jurnal (bandingkan nominal).
5. Bila gagal: JANGAN timpa produksi; catat error + rollback staging.

## Checklist bukti

| Triwulan | Tanggal | Pelaksana | Backup dipakai | Hasil | Catatan |
|---|---|---|---|---|---|
| - | - | - | - | - | (jadwal pertama: triwulan berjalan) |

## Jadwal

- Q1/Q2/Q3/Q4 — minggu kedua bulan pertama triwulan, pemilik: admin platform.
