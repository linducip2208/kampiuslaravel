# TESTING & PERFORMANCE
- Full suite: 164 passed / 0 failed (php artisan test, 21 Nov 2026 env).
- Pint: passed (1 file auto-fixed: InputNilai imports).
- Route: dashboard + dashboard.pimpinan OK.
- Performance: InputNilai 2N -> 2 query (preload lock); Api Tagihan +3 relasi eager; dashboard guarded Schema::hasTable agar tidak 500 bila tabel belum ada.
- Belum: browser test, responsive 360-1920 manual, screenshot docs/screenshots/*.
