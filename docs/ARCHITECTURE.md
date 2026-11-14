# ARCHITECTURE — KAMPUS ERP
- Laravel 13 + Livewire 4 + Spatie Permission 8 + Sanctum 4 (API v1 mobile, v2 publik read-only).
- Pola: Controller tipis (otorisasi + delegasi service), Livewire untuk CRUD interaktif, Service untuk aturan bisnis (KeuanganService, PaymentService, AkademikService, KalenderService).
- Aturan baru: semua multi-write wajib DB::transaction; semua halaman Livewire pakai RequiresRoles + owner-scope server-side.
