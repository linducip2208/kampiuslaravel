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
`pembayaran(va_reference)` unik, `tagihan_cicilan` dievaluasi bareng SoftDeletes.
