# Deployment (checklist produksi)

1. `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain-kamu`.
2. `composer install --no-dev --optimize-autoloader`, `php artisan config:cache route:cache view:cache`.
3. MySQL terpisah + backup berkala. **Jangan pernah `migrate:fresh` di produksi.**
4. `php artisan migrate --force` (forward-only), `php artisan storage:link`.
5. HTTPS wajib (Secure cookie + HSTS di proxy), cron `schedule:run`, `queue:work` via supervisor.
6. Isi Integrasi (Tripay prod, FCM, Feeder) + Tes Koneksi sampai OK.
7. Throttle + WAF sesuai kebijakan kampus; hapus akun demo / ganti password.
8. Monitor: `php artisan pail`, log channel production, audit_logs review berkala.
