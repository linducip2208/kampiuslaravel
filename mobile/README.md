# SIMAK Mobile — API Contract (v1)

Base URL: `{APP_URL}/api/v1` (Android emulator: `http://10.0.2.2:8000/api/v1`)
Auth: `Authorization: Bearer <token>` ability `mobile`. Login throttled `10/min`.

| Method | Endpoint | Body | Response |
|---|---|---|---|
| POST | `/login` | `{nim_or_email, password}` | `{token, user}` |
| GET | `/me` | — | `UserResource` |
| POST | `/logout` | — | `{message}` |
| GET | `/jadwal-hari-ini` | — | `{data: JadwalResource[]}` |
| GET | `/krs-aktif` | — | `{data: Krs\|null}` |
| GET | `/nilai-ringkas` | — | `{data: {ipk, total_sks, per_semester, nilai}}` |
| POST | `/absensi/scan` | `{qr_token}` | `201 {message, data}` / `404` QR invalid / `422` expired |
| GET | `/tugas` | — | `{data: []}` |
| POST | `/tugas/{id}/kumpul` | multipart `file` (pdf/doc/docx/jpg/png/zip ≤10MB) + `catatan?` | `201` |
| GET | `/tagihan` | — | `{data: TagihanResource[], ringkasan: {total_tagihan, total_terbayar, total_menunggak}}` |
| GET | `/bimbingan` | — | `{data: []}` |
| POST | `/bimbingan` | `catatan` + `file?` + `judul_id?` | `201` |
| POST | `/tagihan/{id}/va` | — | `201 {reference, va_number, nominal}` / `422` kredensial belum diisi |
| GET | `/notifikasi` | — | `{data: Notifikasi[], meta}` |
| POST | `/notifikasi/{id}/read` | — | `{ok: true}` |
| POST | `/notifikasi/token` | `{fcm_token}` | `{ok: true}` |

Semua endpoint kecuali `POST /login` → `401` tanpa token. Token butuh ability `mobile`, kedaluwarsa 30 hari.

## Struktur Flutter (lengkap, terverifikasi)

```
mobile/
  pubspec.yaml           # http, shared_preferences
  lib/
    main.dart            # /login + MainShell (bottom nav 5 tab) + /qr
    api_config.dart      # baseUrl via --dart-define=API_BASE_URL=
    api_client.dart      # token Sanctum, login/logout, GET/POST
    screens/
      login_screen.dart      # POST login + simpan token
      home_screen.dart       # jadwal + tunggakan + pengumuman + bimbingan
      qr_scan_screen.dart    # input token QR -> POST /absensi/scan
      nilai_screen.dart      # GET /nilai-ringkas + IPK
      tagihan_screen.dart    # GET /tagihan + POST /tagihan/{id}/va
      pengumuman_screen.dart # GET /pengumuman
      bimbingan_screen.dart  # GET /bimbingan
      notifikasi_screen.dart # inbox + tandai dibaca
      profil_screen.dart     # GET /me + logout + daftar FCM token
  test/app_test.dart      # widget test login + bottom nav
```

Status: `flutter analyze` bersih, `flutter test` 2 test hijau.
Jalankan: `cd mobile && flutter pub get && flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1` (emulator) atau IP LAN untuk device fisik.
Untuk build APK/IPA: `flutter create --project-name simak_mobile --org com.kampus.simak .` di dalam `mobile/`, lalu pindahkan `lib/` + `test/` ini (stub sengaja tanpa `android/`/`ios/` agar ringan di repo).
