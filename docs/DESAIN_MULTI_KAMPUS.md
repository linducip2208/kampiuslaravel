# Desain Multi-Kampus — DITOLAK (arsip, JANGAN dieksekusi)

> Keputusan arsitektur (brief master: SINGLE-INSTITUTION, NO MULTI-TENANT,
> NO TENANT ISOLATION): dokumen ini dipertahankan murni sebagai arsip
> rancangan. M-054 = DITOLAK_WITH_EVIDENCE. Setiap kode isolasi tenant,
> middleware tenant, billing tenant, atau `kampus_id` global scope
> DILARANG ditambahkan ke repo ini.

Dokumen asli di bawah garis hanya untuk referensi keputusan.

---

Dokumen ini adalah deliverable desain sesuai syarat BACKLOG M-054:
tanpa desain yang disahkan, tidak ada kode isolasi yang ditulis.
Setelah disahkan → eksekusi Fase 1..5 di bawah.

## 1. Keputusan arsitektur

**Opsi A — shared-schema + `kampus_id` (DIREKOMENDASIKAN).**
Satu database, semua tabel transaksional + master mendapat `kampus_id`
(FK `kampus`), akses dibatasi `KampusScope` (global scope Eloquent).
Alasan: operasional 1 backup, join lintas kampus tetap mungkin untuk
laporan konsolidasi pimpinan, biaya migrasi terkecil.

**Opsi B — database per kampus.** Ditolak untuk tahap ini: N backup,
migrasi N kali, laporan konsolidasi butuh ETL. Dipertimbangkan ulang
bila satu kampus butuh isolasi regulasi penuh.

**Opsi C — schema per tenant (PostgreSQL).** Ditolak: driver produksi
adalah MySQL (lihat `config/database.php`).

## 2. Model data

- Tabel baru `kampus` (id, kode unik, nama, domain, aktif).
- Kolom `kampus_id` nullable-dulu-wajib-belakangan di: users, mahasiswa,
  dosen, prodi, fakultas, kelas, tagihan, pembayaran, krs, nilai, surat,
  pengumuman, notifikasi, audit_logs, settings (per-kampus override).
- Backfill: semua baris eksisting → kampus `KAMPUS-UTAMA`.
- `KampusScope` global: `where kampus_id = aktif()` kecuali model
  `kampus` sendiri dan bila user `superadmin` + header `X-Lintas-Kampus`.
- `AuditLog` ikut `kampus_id` (audit per-tenant, syarat §4 INTEGRASI).

## 3. Matriks permission (tambahan Spatie)

| Role | Cakupan |
|---|---|
| `superadmin` | lintas kampus (dengan header eksplisit, tercatat di audit) |
| `admin_kampus` (baru) | penuh dalam 1 kampus |
| `admin_prodi`, `dosen`, `mahasiswa`, `keuangan`, `pimpinan` | tidak berubah, otomatis ter-scope kampus user |

Permission baru: `kampus.manage` (hanya superadmin), `laporan.konsolidasi`
(pimpinan lintas kampus, read-only).

## 4. Resolusi kampus per request

Middleware `ResolveKampus` (setelah `auth`): subdomain/domain →
`kampus` aktif → `app()->instance('kampus.aktif')` + `config` view
(__`periodeAktif` dkk tetap jalan). Fallback: `kampus_id` milik user.
API mobile: klaim `kampus_id` di Sanctum token abilities
(`abilities:mobile,kampus:{id}`) — KONTRAK MOBILE BERUBAH, butuh
rilis Flutter bersamaan (lihat M-059).

## 5. Rencana migrasi (5 fase, tanpa downtime tulis)

1. **Fase 1**: tabel `kampus` + seed KAMPUS-UTAMA; tanpa ubah bisnis.
2. **Fase 2**: tambah `kampus_id` nullable + backfill + index;
   deploy tanpa scope (mode bayangan, verifikasi hitungan per kampus).
3. **Fase 3**: aktifkan `KampusScope` + `ResolveKampus` + role
   `admin_kampus`; kolom jadi NOT NULL; tes IDOR lintas kampus.
4. **Fase 4**: `AuditLog` + settings per-kampus; laporan konsolidasi.
5. **Fase 5**: hapus mode bayangan; drill backup/restore per kampus.

Rollback tiap fase: migrasi `down()` + feature flag
`tenant.mode` (`single` | `bayangan` | `multi`).

## 6. Kriteria pengesahan (checklist penandatangan)

- [ ] Opsi A disetujui pimpinan + pemilik data.
- [ ] Daftar tabel final + pemilik tiap fase.
- [ ] Jendela backfill + estimasi durasi lock disetujui DBA.
- [ ] Kontrak mobile Flutter baru disepakati.
- [ ] Drill restore per-kampus lolos 1x sebelum Fase 3.

## 7. Status

**DITOLAK** — keputusan arsitektur single-institution (brief master).
Bagian 1–6 di atas adalah arsip rancangan yang tidak berlaku.
