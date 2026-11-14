# MEGA SECURITY REPORT — temuan terverifikasi file+baris

Sumber: audit forensik `bootstrap/app.php`, `routes/web.php`, `routes/api.php`,
`app/Http/Controllers`, `app/Livewire`, `database/seeders/RolesPermissionsSeeder.php`.
Tanggal: 2026-11-14.

## A. Postur saat ini (fakta)

- Alias middleware: `role, permission, role_or_permission, abilities, ability`
  (`bootstrap/app.php:25-31`); throttle API `L33`; CSRF kecuali `payment/callback`
  (`L23`, wajar untuk webhook).
- Direktori `app/Http/Middleware/` TIDAK ADA — tidak ada middleware custom misterius.
- Policies/Gates: NOL (`app/**/Policy*.php` = 0; `Gate::|->authorize(|@can` = 0 hit).
  Pengganti manual: `AuthorizesMahasiswaData.php:15-26`,
  `Livewire/Concerns/RequiresRoles.php:17-28`.
- 15 permission di-seed (`RolesPermissionsSeeder.php:16-32`), **0 dipakai di kode**.
- Sanctum default; token mobile `createToken('mobile',['mobile'])`
  (`Api/V1/AuthController.php:31`); API v1 di `auth:sanctum + abilities:mobile`.
- Upload: `mimes`+`max` di ImporCsv, BayarManual, MateriCrud, KumpulTugas, API Tugas,
  Bimbingan; nama file hash (`store(...)`) — traversal via input tidak mungkin.
- Unduh dokumen: whitelist `array_key_exists + base_path(docs/.md)`
  (`PublikController:88-91`); path export PDDikti fixed
  (`PddiktiService.php:61`); `streamDownload` tanpa path user.

## B. Temuan prioritas (ID, lokasi, dampak, perbaikan)

| ID | Lokasi | Dampak | Perbaikan (Wave) |
|---|---|---|---|
| S1 | `BayarController:94-96` fallback `PMBVA-` settle tanpa signature valid | PALSU: callback PMB tanpa signature bisa melunasi | A: tolak bila `verifyCallback` null |
| S2 | `PmbVaService:56-68` tanpa tx/lock | Double-callback bersamaan lolos cek | A: tx + `lockForUpdate` |
| S3 | `BayarManual:58-75` check-then-create tanpa lock | TOCTOU: 2 bukti manual ganda | A: lock baris tagihan dalam tx |
| S4 | `PaymentService:111-119` reuse-VA tanpa lock | Klik ganda → 2 VA pending | A: unique partial / lock |
| S5 | `PddiktiController:38-42` `act` arbitrer | Aksi tak terduga via API | A: allowlist `act` |
| S6 | Export PDDikti (`web.php:245`) tanpa audit log | Exfiltrasi nilai tak terlacak | A: tulis `AuditLog` tiap export |
| S7 | Verify Surat (`SuratController:34-36`) & KTM (`KtmController:41`) publik tanpa signature | Enumerasi nomor/NIM | B: QR signed ala `DokumenTte` |
| S8 | 0 policy, 0 `permission:`/`can:` di routes | Otorisasi kasar per-role; role baru berisiko | A→B: Policies + migrasi ke `can:` |
| S9 | `Pengajuan` IPK input manual (`:42,188,253`) | Batas SKS bisa digelembungkan user | A: IPK dari `AkademikService` |
| S10 | Webhook tanpa nonce/timestamp (`BayarController:79-116`) | Replay dalam jendela status | B: idempotency-key + log event |

## C. Yang sudah benar (pertahankan)

- Signature Midtrans (sha512+`hash_equals`), Xendit (callback token), Tripay
  (HMAC-SHA256 raw body + cek event); `SimulatedAdapter` ditolak di prod (`:82-84`).
- Idempotensi parsial: `settleByReference` return bila `verified`
  (`PaymentService:145-147`); `verifyPembayaran` return bila bukan pending (`:284`);
  `postPenerimaan` `firstOrCreate` + unique DB (`keuangan_phase2.php:26`).
- Kepemilikan manual di Khs/Transkrip/Krs/Kwitansi/Surat/KTM/Bayar/API Tagihan/
  Notifikasi/Tugas/Tiket — jangan dihapus saat migrasi ke Policy, jadikan basisnya.

## D. Kriteria selesai Wave A (test-driven)

- Tes replay: kirim callback sama 2x paralel → tepat 1 pelunasan + 1 jurnal.
- Tes TOCTOU: 2 submit bukti manual bersamaan → tepat 1 `pending`.
- Tes IDOR: mahasiswa A akses KHS/transkrip/tagihan/kuitansi/surat milik B → 403
  (web + API). Tes cross-prodi bila relevan.
- Tes otorisasi: role tanpa permission → 403 pada route `can:` baru.
- Tes traversal: `../`, null-byte, ekstensi ganda pada semua upload → ditolak.
