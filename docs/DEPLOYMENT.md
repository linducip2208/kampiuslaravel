# DEPLOYMENT
- Laravel Cloud direkomendasikan (skill deploying-to-cloud). Alternatif: PHP 8.3 + MySQL + queue worker + scheduler.
- Pre-deploy: composer install --no-dev, npm run build, php artisan migrate --force (JANGAN fresh/wipe), config:cache, route:cache, view:cache.
- Terverifikasi 2026-11-14: route:cache + view:cache + config:cache sukses di repo ini (termasuk rute closure), halaman / & /dashboard lolos tes dengan cache aktif; jalankan optimize:clear setiap selesai verifikasi agar dev tidak basi.
- Env wajib: APP_ENV=production, APP_DEBUG=false, APP_KEY terisi, DB_*, SESSION_SECURE_COOKIE=true (HTTPS), SESSION_SAME_SITE=lax, PAYMENT_* (driver non-simulated di prod), webhook secret min 16, mail, storage link.
- Post-deploy: verifikasi /health (db up + pending_migrations 0), login per-role, /dashboard, VA callback signature, scheduler (tagihan:ingatkan 08:00, denda:terapkan 01:00) + backup DB terjadwal.
