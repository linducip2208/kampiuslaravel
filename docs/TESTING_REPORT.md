# TESTING_REPORT (2026-11-14)

- Runner: `php artisan test` (PHPUnit 12.5, RefreshDatabase, sqlite :memory:).
- Hasil: **204/204 hijau, 1197 asersi.** Tidak ada tes yang di-skip/disable untuk menghijaukan hasil.
- Cakupan: auth+RBAC+IDOR (SecurityTest), CRUD modul (MasterData, DosenData, UserData, Kurikulum, KrsFlow, Keuangan, KeuanganPhase2, PmbFlow, SkripsiFlow, KelulusanMbkm, Operasional, FiturBaru, CetakDokumen, Absensi, MobileApi, ApiGuard, Integrasi, ComfyUI, AiFeature, DendaRule, Neraca, Pencarian, Prasyarat, Calculation, DashboardPerf (<100 query, terukur 93), Health, RouteGuard, Hardening, SecurityConfig, CacheStore, TahunAkademik (factory unik), dan lain-lain (48 file).
- Regresi kunci: RouteGuardTest (middleware rute), HealthTest (migrasi tertunda), DashboardPerfTest (ambang query), CacheStoreTest (default serialisasi aman).
- Tidak tercakup (jujur): browser/E2E, responsif 9 breakpoint, screenshot visual, beban/skala data besar, restore backup sungguhan.
- Cara jalan: `php artisan test --compact`; satu file: `php artisan test --filter=NamaTest`.
- Pint: bersih (`vendor/bin/pint --dirty`).
