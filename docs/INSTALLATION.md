# Instalasi (lokal, Laragon/XAMPP)

```bash
git clone https://github.com/linducip2208/kampiuslaravel.git kuliah
cd kuliah
composer install
cp .env.example .env   # lalu isi DB_* (MySQL, database `kuliah`)
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Login demo (password dari admin — lihat seeder/akun yang dibuat):
superadmin / dosen / mahasiswa `@kampus.ac.id`.

Antrean (untuk Generate Gambar AI): `php artisan queue:work`.
Scheduler (bila dipakai): `php artisan schedule:run`.
