# DEPLOYMENT
- Laravel Cloud direkomendasikan (skill deploying-to-cloud). Alternatif: PHP 8.3 + MySQL + queue worker + scheduler.
- Pre-deploy: composer install --no-dev, npm run build, php artisan migrate --force (JANGAN fresh/wipe), config:cache, route:cache, view:cache.
- Env wajib: APP_KEY, DB_*, PAYMENT_* (driver non-simulated di prod), webhook secret, mail, storage link.
- Post-deploy: verifikasi /health (db up), login per-role, /dashboard, VA callback signature, backup DB terjadwal.
