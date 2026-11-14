# DEPLOYMENT_GUIDE (konsolidasi INSTALLATION.md + DEPLOYMENT.md + verifikasi)

## Prasyarat
PHP 8.3, MySQL 8, Node 20, queue worker, scheduler cron (`* * * * * php artisan schedule:run`).

## Deploy
1. `composer install --no-dev --optimize-autoloader`
2. `cp .env.example .env` lalu isi: APP_ENV=production, APP_DEBUG=false, APP_KEY (generate), DB_*, SESSION_SECURE_COOKIE=true, SESSION_SAME_SITE=lax, PAY_* (driver NON-simulated), webhook.secret (min 16), mail, storage.
3. `php artisan key:generate` (sekali) → `php artisan storage:link`
4. `php artisan migrate --force` (JANGAN fresh/wipe) → cek `/health` harus `pending_migrations: 0`
5. `npm install && npm run build`
6. `php artisan config:cache && php artisan route:cache && php artisan view:cache` (terverifikasi sukses 2026-11-14; jalankan `optimize:clear` bila anomali)
7. Worker + scheduler aktif; pastikan `denda:terapkan` 01:00 & `tagihan:ingatkan` 08:00 berjalan.

## Verifikasi pasca-deploy
- `/health` 200; login 6 peran demo (ganti password demo!); dashboard; 1 VA end-to-end (sandbox gateway); callback signature; ekspor 1 CSV; `migrate:status` bersih.

## Rollback
- Kode: checkout tag/commit sebelumnya + rebuild front-end. DB: restore backup (lihat BACKUP_RESTORE) — JANGAN rollback migrasi yang sudah menulis data finansial tanpa berita acara.
