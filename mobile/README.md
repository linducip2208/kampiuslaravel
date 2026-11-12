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
| GET | `/pengumuman?target=&per_page=` | — | `{data: PengumumanResource[], meta}` |

Semua endpoint kecuali `POST /login` → `401` tanpa token.

## Struktur Flutter (stub)

```
mobile/
  pubspec.yaml
  lib/
    main.dart            # routes: /login /home /qr /nilai /tagihan
    api_config.dart      # baseUrl via --dart-define=API_BASE_URL=
    screens/
      login_screen.dart  # TODO: POST login + simpan token (shared_preferences)
      home_screen.dart   # TODO: gabung jadwal-hari-ini + tagihan + pengumuman
      qr_scan_screen.dart# TODO: qr_code_scanner_plus -> POST /absensi/scan
      nilai_screen.dart  # TODO: GET /nilai-ringkas + chart IPK
      tagihan_screen.dart# TODO: GET /tagihan
```

Jalankan setelah `flutter create` penuh (opsional):
`flutter create --project-name simak_mobile --org com.kampus.simak .` di dalam `mobile/`,
lalu pindahkan `lib/` ini. Stub ini sengaja tanpa `android/`/`ios/` agar ringan di repo.
