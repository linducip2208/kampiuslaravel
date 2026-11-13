# DEPLOYMENT
- Laravel Cloud direkomendasikan (skill deploying-to-cloud). Alternatif: PHP 8.3 + MySQL + queue worker + scheduler.
- Pre-deploy: composer install --no-dev, npm run build, php artisan migrate --force (JANGAN fresh/wipe), config:cache, route:cache, view:cache.
- Env wajib: APP_ENV=production, APP_DEBUG=false, APP_KEY terisi, DB_*, SESSION_SECURE_COOKIE=true (HTTPS), SESSION_SAME_SITE=lax, PAYMENT_* (driver non-simulated di prod), webhook secret min 16, mail, storage link.
- Post-deploy: verifikasi /health (db up + pending_migrations 0), login per-role, /dashboard, VA callback signature, scheduler (tagihan:ingatkan 08:00, denda:terapkan 01:00) + backup DB terjadwal.
