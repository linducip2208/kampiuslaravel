# INTEGRATION_GUIDE

Konfigurasi terpusat di halaman admin Integrasi (tidak ada kredensial di repo).
Secrets dibaca dari tabel settings; defaults non-rahasia di config/*.php.

## Payment gateway (adapter di app/Services/Payment/*)
- Driver: simulated (dev saja — DITOLAK di production), tripay, midtrans, xendit.
- Pilih di Integrasi → kredensial wajib lengkap atau transaksi ditolak dengan pesan jelas (bukan 500).
- Panggilan driver di-retry 3x (jeda 200ms) untuk kegagalan transient; gagal permanen → RuntimeException → 422/502 konsisten (teruji driver flaky).
- VA reuse pending kanal sama (anti dobel-klik); tolak bila tagihan lunas (422).

## Webhook & callback
- Masuk v2: HMAC-SHA256, lihat API_DOCUMENTATION.
- Callback payment: lihat adapter + BayarController::callback.

## Notifikasi
- WA via WaService (butuh no_wa user + gateway; tanpa itu pesan masuk inbox notifikasi — idempoten via notif_h7_at/notif_hp7_at).
- Broadcast pengumuman (admin, role-gated). Scheduler: `tagihan:ingatkan` 08:00.

## PDDikti
- Rute pddikti.* (pimpinan|superadmin): index/export/push/sync/sinta/garuda/tte.
- Tanpa kredensial Feeder yang valid SEMUA status "gagal/tertunda" adalah jawaban jujur — jangan klaim sync sukses.

## AI & media
- Chatbot/analitik/at-risk/generator: butuh kredensial provider (ComfyUI/OpenAI dst) di Integrasi.
- Tanpa kredensial: fitur nonaktif aman, bukan respons fabrikasi. AI tidak mengambil keputusan akademik otomatis.

## Monitoring
- `/health` → db + pending_migrations (503 bila ada migrasi belum jalan).
- Audit log: admin.audit. Log Laravel: `php artisan pail`.
