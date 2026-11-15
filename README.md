# KAMPUS — University ERP & Academic Management System

Sistem informasi kampus terpadu: PMB → akademik (KRS/KHS/transkrip) → LMS & CBT →
keuangan (tagihan, VA/QRIS, jurnal, GL, payroll) → riset & pengabdian → wisuda,
alumni & tracer study. Satu database, satu sumber kebenaran per modul
(`AkademikService`, `KeuanganService`, `JadwalService`, ...).

Status: **60/66 item backlog selesai, 303 tes hijau, skor bukti 90/100**.
Lihat `docs/MEGA_BACKLOG.md` (sumber kebenaran cakupan),
`docs/MEGA_SCORECARD.md` (skor per kategori + bukti),
`docs/MEGA_IMPLEMENTATION_LOG.md` (catatan berjalan),
`docs/DESAIN_MULTI_KAMPUS.md` (menunggu pengesahan).

## Syarat

- PHP 8.3 + Composer, MySQL 8, Node 18+ (Vite).
- ComfyUI lokal opsional (generate aset `public/img/*-wan.png`, lihat
  `docs/COMFYUI.md`); landing memakai aset WAN lokal, tanpa unduhan eksternal.

## Jalan cepat (dev)

```bash
cp .env.example .env
php artisan key:generate
# samakan DB_* di .env dengan MySQL lokal (default: kuliah@127.0.0.1)
php artisan migrate --force
php artisan db:seed --class=RolesPermissionsSeeder
php artisan storage:link
npm install && npm run dev   # atau: npm run build
php artisan serve             # http://127.0.0.1:8000 (landing publik di /)
```

Login awal: buat user lalu tetapkan role
(`superadmin|admin_prodi|keuangan|dosen|mahasiswa|pimpinan|keuangan`)
atau pakai factory/seeder yang ada.

## Tes

```bash
php artisan test --compact            # seluruh suite (sqlite in-memory)
php artisan test --filter=NamaTes     # satu file/tes
vendor/bin/pint --dirty               # format sebelum commit
```

Konvensi: setiap perubahan perilaku wajib ada tes merah-hijau;
`docs/MEGA_TEST_REPORT.md` merangkum bukti per wave.

## Struktur (ringkas)

- `app/Services/` — logika terpusat (`AkademikService`, `KeuanganService`,
  `JadwalService`, `QuizService`, `GlService`, `ObeService`, ...).
- `app/Livewire/` — UI per modul (`Krs/`, `Keuangan/`, `Lms/`, `Riset/`, ...).
- `app/Http/Controllers/Api/{V1,V2}/` — API mobile (Sanctum) + publik v2
  (throttle + HMAC webhook). Kontrak: `tests/Feature/WaveCBatch2Test.php`.
- `database/migrations/` — aditif saja; JANGAN `migrate:fresh`/`db:wipe`
  di database berisi data (lihat `docs/BACKUP_RESTORE.md`).
- `docs/` — ~50 dokumen; mulai dari `MEGA_BACKLOG.md`.

## Operasional

- Scheduler (`routes/console.php`): pengingat tagihan, denda, rollover
  periode, segel kuis kedaluwarsa, penyusutan aset. Backup: `backup:create`
  + `backup:verify` (lihat `docs/BACKUP_RESTORE.md`).
- Health: `GET /health` (DB + `pending_migrations` harus 0).
- Integrasi (Feeder, gateway, WA, FCM, SSO, TTE): halaman
  Admin → Integrasi; kredensial di tabel `settings`, bukan di kode
  (lihat `docs/INTEGRASI.md`). Status kesiapan: `docs/MEGA_BACKLOG.md`
  Wave D — 5 item menunggu kredensial/konektivitas nyata.
