# MEGA AUDIT 2026 — Forensik Repository (baseline terverifikasi)

Tanggal: 2026-11-14. Metode: inspeksi file aktual + 4 agen audit paralel + `php artisan test`.
Tidak ada angka yang disalin dari dokumen lama tanpa verifikasi ulang.

## 1. Baseline aktual (perintah verifikasi di §8)

| Area | Aktual | Klaim lama | Status klaim |
|---|---|---|---|
| Models (`app/Models`) | 74 | "82+" | SALAH |
| Migrasi (`database/migrations`) | 53 | — | — |
| Livewire (`app/Livewire`) | 44 komponen (1 trait Concerns) | — | — |
| Controller (`app/Http/Controllers`) | 32 file (1 trait Concerns + base) | — | — |
| Blade (`resources/views`) | 88 file | — | — |
| Test files (`tests/`) | 44 file | "48 file" (TESTING_REPORT) | SALAH |
| Tes hijau | 212/212, 1223 asersi | "207/207" (TESTING_REPORT) vs "212" (commit) | Commit benar, report basi |
| Factory / Seeder | 15 / 4 | — | — |
| Routes | ±130 baris `route:list` | — | — |
| Docs | 46 file `.md` | — | — |
| Git | bersih, `main` = `origin/main` @ `10baa98` | — | — |

## 2. Koreksi inkonsistensi dokumen lama

1. `DEVELOPMENT_MASTER_1130.md` vs `DEVELOPMENT_BACKLOG.md` (1.135 vs 831): keduanya
   TIDAK direkonsiliasi di ronde ini — file MEGA_* menggantikan keduanya sebagai sumber
   kebenaran. Jangan rujuk angka lama.
2. `DEVELOPMENT_SCORE.md`: total tidak konsisten — diganti `MEGA_SCORECARD.md`
   (setiap skor menunjuk ke bukti file+baris).
3. `TESTING_REPORT.md` ("207/207, 48 file"): basi. Aktual 2026-11-14 = **212/212,
   1223 asersi, 44 file** (output `php artisan test --compact` tersimpan di
   `MEGA_TEST_REPORT.md`).
4. Klaim VERIFIED meragukan: `FEATURE_MATRIX.md` "TugasController scope" —
   controller tsb TIDAK ADA (hanya Livewire + closure route). Status materi/tugas
   diturunkan ke PARTIAL (tanpa tes khusus). Rincian di `MEGA_FEATURE_INVENTORY.md`.

## 3. Temuan kritis (wajib Wave A)

- F1. `BayarController:94-96` — callback prefix `PMBVA-` di-settle via `PmbVaService`
  walau `verifyCallback` null → **callback PMB tanpa signature valid tetap melunasi**.
- F2. `PmbVaService:56-68` — settle tanpa tx/lock (double-callback bisa lolos cek).
- F3. `BayarManual:58-75` — cek `exists pending` tanpa lock (TOCTOU).
- F4. `PaymentService:111-119` — reuse-VA check-then-create tanpa lock (klik ganda → 2 VA).
- F5. `KhsController:34` — `'ip' => ringkasan['ipk']`: **IPS ditampilkan = IPK global**.
- F6. `WaliList:67-70` — IPK = `AVG(bobot)` tanpa bobot SKS (inkonsisten dgn `AkademikService:39`).
- F7. `Pengajuan.blade:14-15` + `Pengajuan:42,188,253` — IPK input manual → `ipk_snapshot`
  dipakai validasi SKS (`:215`): user bisa menggelembungkan batas SKS sendiri.
- F8. 15 permission Spatie di-seed, **0 dipakai** di kode (semua via `role:`).
  Risiko: role baru/role campuran lolos cek kasar; tidak ada policy/Gate sama sekali.
- F9. `PddiktiController:38-42` — parameter `act` arbitrer; export penuh tanpa audit log.
- F10. Verifikasi publik Surat/KTM tanpa signature (enumerable); KHS/Transkrip sudah
  pakai `DokumenTte` signed — standarkan ke pola tsb.
- F11. `DashboardController:276` hilangkan denda vs `:46` pakai denda (dua angka
  "penerimaan" berbeda di produk yang sama).
- F12. `ringkasan()`/KHS/Transkrip tanpa filter `is_locked` (lock hanya blokir edit).

## 4. Fitur mati / yatim / duplikat

- D1. `Quiz/QuizSoal/QuizJawaban` — model yatim: tanpa route/controller/Livewire/view/
  relasi. Pilihan: bangun mesin ujian Wave C, atau hapus model (butuh migrasi drop).
- D2. `Beasiswa.kuota` — field ada, tidak pernah dibaca/didecrement di kode.
- D3. `TagihanCicilan` tanpa SoftDeletes (tabel keuangan lain punya).
- D4. `pembayaran.reference` nullable-unique (NULL boleh duplikat — aman, tapi catat).
- D5. Duplikat nama: dua `ExampleTest` (Feature + Unit) — ganti nama Unit.
- D6. `Validasi KRS` terpusat bagus (`AkademikService:168-275` + re-validasi di
  `Pengajuan:238-246` & `PersetujuanPa:57`) — pertahankan pola ini sebagai contoh.

## 5. Aturan bisnis yang hanya ada di UI (migrasi ke service, Wave A)

- B1. IPK manual dari input user (§3-F7) → hitung server-side dari `Nilai`.
- B2. Batas SKS di Blade (`pengajuan.blade:14-15`) → enforce di `validasiKrs` saja.
- B3. Status `is_locked` hanya dipatuhi form input, tidak oleh laporan.

## 6. Cakupan menu/aksi (ringkasan verifikasi)

- 7 grup `role:` di `web.php` + induk `auth`; API v1 di `auth:sanctum+abilities:mobile`.
- `RouteGuardTest` + `RouteReferenceTest` hijau — tidak ada route mati yang dirujuk view.
- Browser/E2E: TIDAK ADA (tidak ada screenshot terarsip). Wave B wajib tambah.

## 7. Keputusan arsitektur ronde MEGA

- K1. Otorisasi: tetap Spatie, tapi migrasi bertahap `role:` → `permission:`/`can:` +
  Policies per model (squad 10). Tidak ada penambahan `campus_id` sebelum desain
  isolasi multi-kampus disahkan (dilarang oleh aturan Fase 5).
- K2. Target 1.500 item DITOLAK sebagai target angka (lihat `MEGA_BACKLOG.md`):
  backlog berisi temuan nyata + turunan terukur, bukan fabrikasi.
- K3. `Quiz yatim`: Wave C membangun mesin ujian di atas `BankSoal` (bukan di atas
  model yatim) — model yatim direlasikan/dihapus dalam migrasi itu juga.

## 8. Perintah re-verifikasi (ulangi kapan saja)

```
(Get-ChildItem app/Models -Recurse -Filter *.php).Count
(Get-ChildItem tests -Recurse -Filter *Test.php).Count
php artisan test --compact
```
