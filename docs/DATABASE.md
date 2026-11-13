# DATABASE — audit Nov 2026 (read-only, tanpa migrasi destruktif)
- Uang benar: unsignedBigInteger untuk nominal/terbayar/denda.
- Risiko fresh migrate: FK ganda prodi/mahasiswa/kelas/tahun_akademik (migrasi awal vs 2026_11_13_000008). Jangan migrate:fresh sebelum dibersihkan; gunakan migrate biasa.
- Unique bocor bila nullable: krs_detail(krs,kelas NULL), tagihan(mhs,tahun NULL,jenis), krs(mhs NULL,tahun NULL). Rencana: buat kolom identitas NOT NULL via migrasi aman bertahap + validasi.
- 0 softDeletes: tagihan/pembayaran/mahasiswa/krs/nilai dihapus permanen. Rencana: tambah softDeletes + restrict delete.
- N+1 diperbaiki sesi ini: Api Tagihan with(tahunAkademik,cicilan,beasiswa); InputNilai preload lock + transaction.
- Sisa N+1: PersetujuanPa with details saja; VerifikasiBayar kurang nested tahunAkademik; AkademikService kuota per-kelas (preload COUNT GROUP BY).
