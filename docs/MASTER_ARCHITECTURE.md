# MASTER_ARCHITECTURE (ringkas; detail: docs/ARCHITECTURE.md)

## Lapisan
Request → middleware (auth, role Spatie, throttle) → Controller tipis (otorisasi + delegasi) → Service aturan bisnis (KeuanganService, PaymentService+adapter, AkademikService, KalenderService, PmbVaService, SuratNomorService, BeritaAcaraService) → Eloquent Model (fillable, cast presisi, SoftDeletes keuangan) → MySQL (FK + index status/komposit).
Livewire = interaksi CRUD (RequiresRoles + owner-scope + transaction + lock + confirm + loading).

## Domain & batasan
PMB | Akademik (KRS/Nilai/Kurikulum) | Keuangan (Tagihan/Bayar/Jurnal) | Kemahasiswaan (Cuti/Alumni) | Skripsi-Sidang-Yudisium-Wisuda | Surat-TTE | LMS-Absensi | Kalender-Helpdesk-Pengumuman | Analitik-AI | Laporan-PDDikti | Admin (User/Master/Impor/Inte-grasi/Audit).

## Aturan invarian
1. Multi-tulis = DB::transaction + lockForUpdate; cek ulang di dalam (kuota, status, nominal).
2. Idempotency: firstOrCreate/updateOrCreate + early-return status (settle, jurnal, VA reuse, daftar ulang, rekonsiliasi, reminder H-7/H+7).
3. Otorisasi server-side + owner-scope; UI hide bukan keamanan (dibuktikan SecurityTest + RouteGuardTest).
4. Uang = unsignedBigInteger; persen di-clamp; denda ditimpa bukan diakumulasi; jurnal tak pernah di-update (hanya insert reversal).
5. Guarded queries (Schema::hasTable) di permukaan publik/dashboard.
6. Serialisasi cache objek DITOLAK framework (jangan lawan; agregat dihitung langsung).
7. Draft/arsip: soft-delete keuangan; hapus permanen = SQL + berita acara.

## Alur data kunci
PMB (daftar→VA→verify→nilai→lulus→daftarUlang[NIM+tagihan awal]) → KRS (pengajuan→PA setuju, prasyarat+kuota+IPK) → Nilai (input→kunci→remedial≤55) → Sidang→Yudisium (SKL+alumni)→Wisuda (batch+kuota+PIN ijazah). Keuangan paralel: tagihan→VA/manual→verify→jurnal→(refund)→rekonsiliasi→neraca; denda otomatis harian.

## Multi-kampus (persiapan, bukan klaim)
Hierarki Fakultas→Prodi→TahunAkademik→Kelas→Mahasiswa mendukung banyak prodi/fakultas. Scope kampus/global belum ada — butuh kolom + scope + migrasi (lihat ROADMAP).
