# BACKUP_RESTORE

## Backup (jadwalkan via cron/systemd, di luar scheduler Laravel)
1. Database MySQL (tambahkan `--single-transaction`):
   `mysqldump -h 127.0.0.1 -u USER -p KULIAH_DB --single-transaction --routines --events | gzip > kampius-$(date +%F).sql.gz`
2. File upload: `storage/app/public/` (bukti bayar, berkas PMB, materi) — sinkron ke object storage / salin harian.
3. `.env` (terpisah, terenkripsi) + daftar versi migrasi (`php artisan migrate:status`).
4. Retensi saran: harian 7 hari, mingguan 4 minggu, bulanan 12 bulan. Uji restore tiap triwulan.

## Restore
1. Buat DB kosong → `gunzip < backup.sql.gz | mysql -u USER -p KULIAH_DB`.
2. `php artisan migrate --force` (harus 0 pending; verifikasi via `/health` → `pending_migrations: 0`).
3. Kembalikan `storage/app/public/` + `php artisan storage:link`.
4. Verifikasi: login per-role, buka dashboard, cek 1 pembayaran + 1 KRS.

## Catatan
- Jangan pernah `migrate:fresh`/`db:wipe` di produksi (data tak kembali).
- Soft delete tagihan/pembayaran/jurnal melindungi bukti; hapus permanen hanya via SQL langsung dengan berita acara.
- File `.env` dan kredensial JANGAN masuk backup repo.
