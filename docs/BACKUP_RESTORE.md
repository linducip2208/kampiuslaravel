# BACKUP_RESTORE

## Backup (jadwalkan via cron/systemd, di luar scheduler Laravel)
1. Database MySQL (tambahkan `--single-transaction`):
   `mysqldump -h 127.0.0.1 -u USER -p KULIAH_DB --single-transaction --routines --events | gzip > kampius-$(date +%F).sql.gz`
2. File upload: `storage/app/public/` (bukti bayar, berkas PMB, materi) — sinkron ke object storage / salin harian.
3. `.env` (terpisah, terenkripsi) + daftar versi migrasi (`php artisan migrate:status`).
4. Retensi saran: harian 7 hari, mingguan 4 minggu, bulanan 12 bulan. Uji restore tiap triwulan.

## Backup teruji via artisan (M-047, Wave B Batch 1)

Selain mysqldump MySQL di atas, tersedia backup portabel lintas-driver:

1. `php artisan backup:create` — dump semua tabel (chunk 500) ke
   `storage/app/private/backups/<STAMP>/` (`*.json.gz` + `manifest.json`,
   penanda `backups/latest.txt`).
2. `php artisan backup:verify [stamp]` — gunzip + validasi JSON tiap tabel
   dan cocokkan jumlah baris dengan database. Keluar 0 = VALID.
3. Teruji di `tests/Feature/WaveBBatch1Test.php::test_backup_create_dan_verify`.
4. Jadwalkan `backup:create` harian via cron/systemd; uji `backup:verify`
   tiap triwulan (menggantikan uji restore manual §Restore).

## Restore
1. Buat DB kosong → `gunzip < backup.sql.gz | mysql -u USER -p KULIAH_DB`.
2. `php artisan migrate --force` (harus 0 pending; verifikasi via `/health` → `pending_migrations: 0`).
3. Kembalikan `storage/app/public/` + `php artisan storage:link`.
4. Verifikasi: login per-role, buka dashboard, cek 1 pembayaran + 1 KRS.

## Catatan
- Jangan pernah `migrate:fresh`/`db:wipe` di produksi (data tak kembali).
- Soft delete tagihan/pembayaran/jurnal melindungi bukti; hapus permanen hanya via SQL langsung dengan berita acara.
- File `.env` dan kredensial JANGAN masuk backup repo.
