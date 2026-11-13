# INSTALLATION & DEPLOYMENT
- Syarat: PHP 8.3, MySQL, Node 20.
- Setup: composer install, cp .env, php artisan key:generate, php artisan migrate (JANGAN migrate:fresh di produksi), npm install, npm run build.
- Jangan commit/push otomatis; webhook payment butuh signature; driver simulated ditolak di production.
