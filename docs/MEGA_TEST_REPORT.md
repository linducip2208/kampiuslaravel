# MEGA TEST REPORT — hasil aktual 2026-11-14

Runner: `php artisan test --compact` (PHPUnit, sqlite memory — sesuai TESTING_REPORT lama).

## Hasil

```
{"tool":"phpunit","result":"passed","tests":212,"passed":212,"assertions":1223,"duration_ms":30272}
```

- 44 file tes, **212/212 hijau, 1223 asersi**.
- Koreksi dokumen lama: `TESTING_REPORT.md` ("207/207, 1202 asersi, 48 file") BASI.
  Klaim commit "212" BENAR. `TESTING_REPORT.md` wajib diperbarui (masuk Wave B docs).

## Inventaris file tes (44)

AbsensiTest, AiFeatureTest, ApiAuthTest, ApiGuardTest, BankSoalTest, CacheStoreTest,
CalculationTest, CetakDokumenTest, ComfyUITest, DashboardPerfTest, DendaOtomatisTest,
DendaRuleTest, DokumenTraversalTest, DosenDataTest, ErrorPageTest, ExampleTest (x2 —
Feature & Unit, DUPLIKAT NAMA), FiturBaruTest, HardeningTest, HealthTest,
IntegrasiTest, JadwalServiceTest, KalenderLockTest, KelulusanMbkmTest,
KemahasiswaanCutiTest, KeuanganPhase2Test, KeuanganTest, KrsFlowTest, KurikulumTest,
MasterDataTest, MobileApiTest, NeracaTest, NilaiTest, OperasionalBaruTest,
PencarianTest, PmbFlowTest, PmbMarketingTest, PrasyaratTest, RouteGuardTest,
RouteReferenceTest, SecurityConfigTest, SecurityTest, SkripsiFlowTest, UserDataTest.

## Lubang cakupan (wajib Wave A/B — bukan untuk menakut-nakuti, tapi jadwal kerja)

1. Tanpa tes khusus: Tugas, Materi (LMS), Quiz engine (belum ada yang bisa dites).
2. Tanpa tes keamanan khusus: replay webhook, TOCTOU manual/VA, IDOR lintas
   mahasiswa per-modul (baru sebagian via HardeningTest), traversal upload
   (baru dokumen: DokumenTraversalTest).
3. Tanpa E2E/browser, tanpa screenshot terarsip, tanpa tes beban/restore
   (diakui jujur di TESTING_REPORT lama §7 — tetap benar).
4. Duplikat `ExampleTest` — ganti nama yang Unit.
5. Perhitungan yang belum di-lock tesnya: IPS vs IPK (`KhsController:34`),
   `AVG(bobot)` WaliList, inkonsistensi denda Dashboard (`:46` vs `:276`).

## Aturan kualitas (mengikat ronde MEGA)

- Setiap perbaikan Wave A wajib bawa tes gagal-dulu (red) lalu hijau.
- Setiap modul Wave B/C wajib: happy path + validasi + otorisasi + 1 kasus
  batas (kuota penuh, VA ganda, callback ganda, jadwal bentrok).
- Dilarang men-skip/menghapus tes agar hijau. Kegagalan diperbaiki di kode.
