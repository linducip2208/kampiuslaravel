# Modul ERP — status implementasi ( diverifikasi baca kode + test )

| Modul | Status | Catatan |
|---|---|---|
| PMB | ✅ | Form publik + berkas upload, verifikasi, lulus, daftar ulang (akun + NIM + tagihan PMB). |
| KRS | ✅ | SKS-maks by IPK, blokir tunggakan, prasyarat, anti-bentrok, cek kuota, approval PA, revisi terkunci pasca-approve. |
| LMS + absensi | ✅ | Materi/tugas/quiz, jurnal, QR + window + auto hadir/telat, EDOM. Rekap 75% via query absensi. |
| Nilai/KHS/transkrip | ✅ | NA=0.3T+0.3UTS+0.3UAS+0.1H, huruf A–E, kunci, IPK tertimbang SKS (`AkademikService`). |
| Skripsi/KP | ✅ | Judul, pembimbing, log bimbingan (owner-check), sidang + nilai penguji, repository. KP memakai alur judul yang sama. |
| Keuangan | ✅ | Tagihan massal, VA Tripay/Midtrans/Xendit + callback signature + status sync, upload manual + verifikasi idempoten, kwitansi, blokir KRS/sidang otomatis. |
| Surat | ✅ | Pengajuan, nomor otomatis anti-kembar (cek), cetak + verifikasi QR publik. |
| Pengumuman/berita | ✅ | pSEO `/prodi`, `/pengumuman`, cover dari ComfyUI opsional. |
| Notifikasi | ✅ | Inbox + broadcast per role + FCM HTTP v1 + API mobile. |
| PDDikti | ✅ | Export CSV mahasiswa/nilai/kelulusan + push record via Feeder (kredensial admin). |
| ComfyUI/WAN | ✅ | Queue/history/view API, job antrean, cover pengumuman, Tes Koneksi. Butuh server ComfyUI + model. |
| Kalender akademik | ⚠️ | Parsial: agenda dashboard (pertemuan + sidang mendatang). Belum ada CRUD kalender tersendiri. |
| Wisuda/kelulusan | ⚠️ | Parsial: status `lulus`, export kelulusan, surat keterangan lulus. Belum ada modul yudisium. |
| Beasiswa/diskon | ❌ | Belum ada. Tagihan memakai nominal penuh. |
| Helpdesk tiket | ❌ | Belum ada. |
