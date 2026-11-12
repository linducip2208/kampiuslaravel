# Audit Baseline — Kampius ERP (SIMAK Kuliah)

Tanggal: 12 Nov 2026. Metode: baca langsung kode + DB MySQL `kuliah` + test suite.
Lima audit spesialis: `AUDIT_ARCH/SECURITY/DB/UI/MODULES.md`.

## Fakta terdeteksi
- Laravel 13.42, PHP 8.3.30, Livewire 4.4.8, Spatie Permission 8.5, Sanctum 4.4, MySQL 8.4.9 (50 tabel).
- 79 route (62 web + 17 API), 12 controller + 17 API controller, 22 komponen Livewire, 36 model, 32 migration, 51 view Blade, 13 file test.
- Jenis: single-campus (Fakultas→Prodi, settings global, tanpa tenant/scope kampus).
- Git: branch main, 1 commit lokal, remote `linducip2208/kampiuslaravel` (belum push — sesuai aturan proteksi).

## Temuan lintas-audit (ringkas)
1. Tanpa `role` middleware di semua route; IDOR di KHS/transkrip/KRS/kwitansi/surat (hanya `auth`).
2. Rumus IPK salah (`avg bobot`, SKS=0) di web; API justru benar.
3. Aksi sensitif Livewire tanpa authorize; callback `simulated` tanpa signature.
4. Upload tanpa `mimes`; fallback `latest('id')` + spoofing parameter.
5. Seluruh modul LMS tanpa FK; pola guard-FK tidak terbentuk saat fresh.
6. PMB daftar ulang tak buat mahasiswa/NIM/tagihan; tak ada upload bukti manual; tak ada berkas PMB.
7. 13+ view Livewire HTML mentah; KRS/KHS tampilkan ID mentah; login belum split; dashboard tanpa chart.
8. API abilities tak ditegakkan; token abadi; login/PMB tanpa throttle.
9. Xendit `inactive→verified` salah; settle tanpa lock; verifikasi tanpa idempotency guard.
10. APP_DEBUG=true; kredensial demo di view login.

Lihat tiap file AUDIT_* untuk bukti file:baris. Status perbaikan: `FINAL_AUDIT.md`.
