# MEGA PERFORMANCE REPORT — temuan + rencana uji

Tanggal: 2026-11-14. `DashboardPerfTest` hijau (query-count aman di data kecil) —
itu BUKAN bukti skala. Berikut fakta + rencana.

## A. Pola query terverifikasi baik (pertahankan)

- `AkademikService:27` eager `Nilai::with(kelas.matakuliah,kelas.tahunAkademik)`.
- `PersetujuanPa:139` paginate(10)+with; `Api Jadwal:32` with 3 relasi.
- `WaliList:67-79` agregasi 2 query.

## B. Masalah terukur (lokasi persis, Wave A/B)

| ID | Lokasi | Masalah |
|---|---|---|
| P1 | `JadwalService:48-56,68-76,178-182` | `get()->contains()` seluruh kelas 1 hari, in-memori, unbounded |
| P2 | `Jadwal Generate:114-117` | `get()` + ~2 query/sel → N+1 |
| P3 | `AkademikService:218-224,260-272` | `exists` per prasyarat + hitung kursi per kelas → N query |
| P4 | `WaliList:82-83` | `latest()->get()->groupBy` muat semua KRS 15 mhs |
| P5 | `Api Jadwal:28-30` | `kelasIds=null` → fallback SEMUA kelas |
| P6 | `ringkasan:29` + KHS/Transkrip | full `Collection` ke Blade (wajar per-mhs, tapi tanpa paginasi lintas) |
| P7 | Pembayaran VA reuse-check `PaymentService:111-115` | tanpa index `(tagihan_id,status)`, andalkan FK index |
| P8 | `PmbVaService` lookup `va_reference` | tanpa index unik |

## C. Rencana benchmark (Wave B, sebelum klaim skala)

Seed 1k → 10k → 50k mahasiswa (factory ada: 15 factory). Ukur per volume:
rute KRS ajukan, KHS, transkrip, dashboard keuangan, rekon CSV, export PDDikti —
catat query-count (via `DB::listen`/telescope-less counter di tes), durasi,
memori. Target awal: p95 < 800ms @1k, tanpa N+1 (assert `DB::getQueryLog` di tes).
Hasil masuk tabel di file ini. DILARANG menambah cache global untuk data
user-spesifik; cache hanya agregat publik + invalidasi eksplisit.

## D. Indeks yang sudah ada vs kurang

Ada: `..._performance_indexes:14-22` (`pembayaran(status,verified_at)`,
`tagihan(status / mhs+status)`). Tambah Wave A: `pembayaran(tagihan_id,status)`,
`tagihan_cicilan` dievaluasi bareng SoftDeletes.

## E. Status perbaikan (2026-11-15, bukti `tests/Feature/PerfDebtTest.php`)

- Hasil ukur budget (`tests/Feature/PerformanceBudgetTest.php`, sqlite):
  KHS & transkrip = 19–21 query TETAP pada 5 vs 60 baris nilai (flat,
  tanpa N+1; plafon 25), `validasiKrs` ≤ 12 query untuk 10 kelas.
  21 query adalah overhead tetap (auth/roles, composer pengumuman+
  periode aktif, settings) — BUKAN per-baris. Bukan klaim p95 produksi.

- P1 SELESAI (Batch 2): overlap pindah ke sisi DB + normalisasi HH:MM:SS.
- P2 SELESAI (2026-11-15): `generate()` preload 1x jadwal terisi + cek
  overlap in-memory (`bentrokDalamKoleksi`, predikat sama dengan versi DB);
  8 kelas = 3 baca + 8 tulis (plafon 30 di tes). `ruangBentrok`/
  `dosenBentrok` DB tidak berubah (dipakai pinjam ruang & validasi slot).
- P3 SELESAI (Batch 2): prasyarat 1 query + kuota 2 query via `kursiTerisiBanyak`.
- P4 SELESAI (Batch 4): `krsStatus` via subquery MAX(id).
- P5 SELESAI (2026-11-15): dosen ter-scope kelasnya; non-dosen dibatasi 200
  baris, bentuk respons tetap array (kontrak mobile utuh).
- P6 DITERIMA: `ringkasan()` full-collection memang dibutuhkan untuk hitung
  IPK; display KHS/Transkrip sudah paginasi (M-024).
- P7 DITERIMA: filter PHP hanya atas pending per tagihan (sedikit baris);
  index `(tagihan_id,status)` Wave A menanggung query-nya.
- P8 SELESAI (2026-11-15, koreksi lokasi: BUKAN `pembayaran` — kolom
  `va_reference` ada di `pmb_pendaftars`): unique `pmb_va_reference_unique`
  + guard duplikat di migrasi. Dokumen ini §B baris P8 & §D lama salah
  menunjuk tabel pembayaran — dikoreksi di sini.
