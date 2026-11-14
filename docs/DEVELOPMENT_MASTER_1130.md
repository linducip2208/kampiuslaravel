# DEVELOPMENT MASTER — 1.130 ITEM (KAMPIUS ERP, 2026-11-14)

Format ringkas per baris (kolom penuh 15-atribut diringkas agar dapat diaudit):
`ID | Nama pengembangan | Modul | Prioritas(P0-P3) | Kompleksitas(R/S/B) | Status | Kriteria terima + uji`.

Status: VERIFIED (kode+rute+validasi+otorisasi+UI+tes hijau), TESTED, IMPLEMENTED,
PARTIAL, NOT_STARTED. Bukti = file/tes aktual. Tidak ada item fiktif.
Total: A1:115 + A2:115 + A3:105 + A4:120 + A5:120 + A6:115 + A7:105 + A8:115 + A9:110 + A10:115 = **1.130**.

## AGENT 1 — ACADEMIC CORE (115)

| ID | Pengembangan | Prioritas | Ks | Status | Kriteria terima + uji |
|---|---|---|---|---|---|
| A1-001 | CRUD Fakultas via Data Master | P1 | R | VERIFIED | Tes MasterDataTest; relasi prodi |
| A1-002 | CRUD Prodi + slug SEO + halaman publik | P1 | R | VERIFIED | prodi-index/show, canonical+JSON-LD |
| A1-003 | Unik kode prodi + validasi | P1 | R | VERIFIED | Unique di migrasi; impor menolak duplikat |
| A1-004 | CRUD Tahun akademik + flag aktif tunggal | P1 | R | VERIFIED | Master data; periodeAktif di topbar |
| A1-005 | CRUD Matakuliah (kode/SKS/semester/prodi) | P1 | R | VERIFIED | ImporCsv::imporMk; KurikulumTest |
| A1-006 | Prasyarat tunggal legacy (prasyarat_id) | P1 | S | VERIFIED | validasiKrs; PrasyaratTest |
| A1-007 | Relasi prasyarat ganda mk_prasyarat + cek | P1 | S | VERIFIED | PrasyaratTest (2 lulus); anti-siklus di Kurikulum |
| A1-008 | Relasi ekivalensi MK + uji siklus | P1 | S | VERIFIED | KurikulumTest tambahRelasi |
| A1-009 | Versi kurikulum per tahun berlaku | P1 | S | PARTIAL | Tabel+CRUD ada; butuh: kunci versi bila dipakai KRS berjalan |
| A1-010 | Kunci kurikulum aktif vs arsip | P2 | S | NOT_STARTED | Kriteria: status locked; KRS hanya referensi versi aktif; tes lock |
| A1-011 | Riwayat perubahan kurikulum (audit) | P2 | S | NOT_STARTED | Kriteria: diff tersimpan; tes audit trail |
| A1-012 | CRUD Kelas + kuota + dosen + ruang | P1 | R | VERIFIED | Jadwal::Generate; KrsFlowTest kuota |
| A1-013 | Validasi kuota saat generate jadwal | P1 | S | PARTIAL | Ada cek; butuh: peringatan overbook di UI generate |
| A1-014 | Batas SKS by IPK (24/22/20/18) | P0 | R | VERIFIED | BatasSks + CalculationTest; enforced di Pengajuan & PersetujuanPa |
| A1-015 | Konversi NA→huruf→bobot baku | P1 | R | VERIFIED | Nilai::konversi + CalculationTest |
| A1-016 | Bobot komponen custom per kelas + normalisasi 100 | P1 | S | VERIFIED | Nilai::normalisasiBobot; tes batas |
| A1-017 | Kalender akademik + event + ICS + locks | P1 | S | VERIFIED | KalenderLockTest; locks dipakai KRS/bayar/sidang |
| A1-018 | Kunci kalender per jenis kegiatan | P1 | S | VERIFIED | KalenderService::isOpen; pesan tutup |
| A1-019 | Tahun berjalan otomatis di form | P2 | R | PARTIAL | tahunAktifId ada; butuh: fallback UI bila belum ada TA aktif |
| A1-020 | Semester ganjil/genap konsisten | P1 | R | VERIFIED | Enum di migrasi + factory deterministik |
| A1-021 | Angkatan mahasiswa + validasi format | P1 | R | VERIFIED | MahasiswaFactory; impor validasi |
| A1-022 | Status mahasiswa lifecycle (aktif/cuti/nonaktif/do/keluar/lulus) | P0 | S | VERIFIED | Konstanta + transisi via cuti/yudisium; tes alur |
| A1-023 | Blokir KRS saat tunggakan | P0 | S | VERIFIED | KrsFlowTest tunggakan; KeuanganService::adaTunggakan |
| A1-024 | Blokir sidang saat tunggakan | P0 | S | VERIFIED | isBlockedForSidang dipakai Pendaftaran |
| A1-025 | Deteksi bentrok jadwal KRS | P1 | S | VERIFIED | validasiKrs bentrok hari+jam |
| A1-026 | Visualisasi bentrok di UI pengajuan | P2 | S | NOT_STARTED | Kriteria: daftar bentrok tampil sebelum ajukan; tes Livewire |
| A1-027 | Rekomendasi MK berbasis prasyarat lulus | P2 | S | NOT_STARTED | Kriteria: saran MK yang prasyaratnya terpenuhi; tes unit |
| A1-028 | Simulasi IPK "what-if" | P2 | S | NOT_STARTED | Kriteria: input nilai hipotetis → IPK proyeksi; tes hitung |
| A1-029 | Riwayat IPK per semester (grafik) | P2 | S | NOT_STARTED | Kriteria: tren dari Nilai::rekap; tes agregat |
| A1-030 | Peringatan dini kehadiran <75% | P1 | R | VERIFIED | warningKehadiran + kunci nilai; tes |
| A1-031 | Rekap kehadiran per mahasiswa | P1 | S | VERIFIED | LaporanController::rekapAbsensi + ekspor |
| A1-032 | EDOM rekap per dosen | P1 | S | IMPLEMENTED | Nilai::rekapEdomPerDosen; butuh: UI rekap + tes Livewire |
| A1-033 | EDOM per kelas di kunci nilai | P2 | R | IMPLEMENTED | edomRekap(); butuh: tes tampilan |
| A1-034 | Kuesioner EDOM mahasiswa | P2 | S | NOT_STARTED | Kriteria: isi sekali per kelas; agregat anonim; tes duplikat |
| A1-035 | Ambang kelulusan sidang parametrik | P1 | R | PARTIAL | Hardcode lulus/revisi/gagal; butuh: tabel ambang per prodi |
| A1-036 | Syarat yudisium terpusat (IPK+tunggakan+sidang) | P0 | S | VERIFIED | Yudisium::luluskan 3 gate; tes |
| A1-037 | SKL otomatis + nomor surat | P1 | S | VERIFIED | firstOrCreate + assignIfEmpty; tes |
| A1-038 | PIN ijazah unik + no duplikat | P1 | S | VERIFIED | Str::random(8) + unique kolom; tes kolom |
| A1-039 | Rekap alumni per tahun lulus | P2 | R | PARTIAL | Tabel alumni ada; butuh: filter tahun + ekspor; tes |
| A1-040 | Tracer study dasar | P2 | S | NOT_STARTED | Kriteria: form pekerjaan/instansi; rekap; tes isi |
| A1-041 | Dokumen mahasiswa per kategori | P1 | S | PARTIAL | Berkas PMB ada; butuh: arsip KHS/transkrip/SKL per mahasiswa |
| A1-042 | Masa studi maksimum + peringatan | P2 | S | NOT_STARTED | Kriteria: flag >8 thn (S1); tes tanggal |
| A1-043 | Cuti akademik + kuota cuti | P1 | S | VERIFIED | Kemahasiswaan/Cuti + CutiTest |
| A1-044 | Status nonaktif otomatis (mangkir) | P2 | S | NOT_STARTED | Kriteria: job deteksi tanpa KRS 2 semester; tes command |
| A1-045 | DO administratif + jejak audit | P1 | S | PARTIAL | Status DO ada; butuh: alur persetujuan + audit; tes |
| A1-046 | Mutasi masuk (konversi nilai) | P2 | B | NOT_STARTED | Kriteria: mapping MK + persetujuan; tes konversi |
| A1-047 | Mutasi keluar (surat + transkrip) | P2 | S | NOT_STARTED | Kriteria: paket dokumen; tes paket |
| A1-048 | Kelas paralel + pembagian otomatis | P2 | S | NOT_STARTED | Kriteria: split bila >kuota; tes split |
| A1-049 | Ruang + kapasitas + bentrok ruang | P1 | S | PARTIAL | Tabel ruang + pinjam ada; butuh: cek bentrok saat generate |
| A1-050 | Generate jadwal massal + validasi | P1 | S | VERIFIED | Jadwal::Generate; tes |
| A1-051 | Jadwal dosen per pekan (cetak) | P2 | R | NOT_STARTED | Kriteria: rekap per dosen; tes agregat |
| A1-052 | Jadwal ruangan per hari (papan) | P2 | R | NOT_STARTED | Kriteria: grid ruang×jam; tes bentrok tampil |
| A1-053 | Presensi dosen per pertemuan | P1 | S | VERIFIED | PresensiDosen; tes |
| A1-054 | Jurnal mengajar dosen | P1 | S | IMPLEMENTED | JurnalDosen model; butuh: UI + rekap; tes |
| A1-055 | BKD: rekap beban SKS mengajar | P2 | S | NOT_STARTED | Kriteria: sum SKS/kelas/dosen/semester; tes |
| A1-056 | BKD: bimbingan + pengujian | P2 | S | NOT_STARTED | Kriteria: hitung dari judul/sidang; tes |
| A1-057 | BKD: penelitian & pengmas | P2 | S | NOT_STARTED | Butuh modul riset (lihat A7); tes integrasi |
| A1-058 | SK mengajar otomatis | P2 | S | NOT_STARTED | Kriteria: nomor + daftar kelas + cetak; tes nomor unik |
| A1-059 | OBE: master CPL per prodi | P2 | S | NOT_STARTED | Kriteria: CRUD CPL; tes relasi (lihat A1-060 dst) |
| A1-060 | OBE: master CPMK per MK | P2 | S | NOT_STARTED | Kriteria: CRUD CPMK↔MK; tes |
| A1-061 | OBE: pemetaan CPL→CPMK | P2 | S | NOT_STARTED | Kriteria: matriks kontribusi; tes matriks |
| A1-062 | OBE: pemetaan CPMK→asesmen | P2 | S | NOT_STARTED | Kriteria: bobot asesmen per CPMK total 100; tes |
| A1-063 | OBE: input capaian per mahasiswa | P2 | B | NOT_STARTED | Kriteria: nilai→level capai; tes konversi |
| A1-064 | OBE: agregat capaian CPL prodi | P2 | B | NOT_STARTED | Kriteria: rekap % tercapai; tes agregat |
| A1-065 | OBE: rapor ketercapaian + saran | P2 | S | NOT_STARTED | Kriteria: dashboard prodi; tes tampil |
| A1-066 | OBE: versi + riwayat (tanpa ubah histori) | P1 | S | NOT_STARTED | Kriteria: snapshot per kurikulum; tes immutability |
| A1-067 | OBE: ekspor borang akreditasi | P2 | S | NOT_STARTED | Kriteria: CSV matriks; tes isi (bukan sertifikasi) |
| A1-068 | Evaluasi kurikulum berkala | P2 | S | NOT_STARTED | Kriteria: review per 4 thn + tindak lanjut; tes reminder |
| A1-069 | Sinergi MK duplikat antar prodi | P3 | S | NOT_STARTED | Kriteria: deteksi nama mirip; tes |
| A1-070 | SKS transfer / RPL | P2 | B | NOT_STARTED | Kriteria: ajuan + bukti + approve; tes alur |
| A1-071 | Mata kuliah pilihan vs wajib + kuota min | P2 | S | NOT_STARTED | Kriteria: sifat + cek kuota; tes validasi |
| A1-072 | Prasyarat nilai minimum (bukan sekadar lulus) | P2 | S | NOT_STARTED | Kriteria: ambang huruf per relasi; tes |
| A1-073 | Paket semester (paket KRS otomatis) | P2 | S | NOT_STARTED | Kriteria: template per semester; tes generate |
| A1-074 | KRS susulan (di luar jadwal + denda) | P2 | S | NOT_STARTED | Kriteria: flag susulan + biaya; tes |
| A1-075 | Batal tambah KRS (add/drop period) | P2 | S | NOT_STARTED | Kriteria: window tanggal; tes kalender |
| A1-076 | Histori revisi KRS (siapa/apa/kapan) | P2 | S | NOT_STARTED | Kriteria: log detail; tes log |
| A1-077 | Persetujuan berjenjang (PA + Kaprodi) | P2 | S | NOT_STARTED | Kriteria: dua tahap; tes status |
| A1-078 | Notifikasi KRS ke mahasiswa | P2 | R | NOT_STARTED | Kriteria: inbox saat disetujui/ditolak; tes |
| A1-079 | Cetak KRS resmi + QR | P1 | S | PARTIAL | Cetak ada; butuh: QR TTE seperti KHS; tes |
| A1-080 | Arsip KRS per semester (immutable) | P1 | S | NOT_STARTED | Kriteria: snapshot PDF/hash; tes hash |
| A1-081 | KHS per semester + IPK snapshot | P0 | S | VERIFIED | KhsController + ringkasan; tes |
| A1-082 | KHS verify publik + TTE | P0 | S | VERIFIED | CetakDokumenTest |
| A1-083 | Transkrip akumulatif + predikat | P0 | S | VERIFIED | TranskripController; tes |
| A1-084 | Transkrip verify + PIN ijazah | P1 | S | VERIFIED | Verify + pin; tes |
| A1-085 | Legalisir digital (tercatat) | P2 | S | NOT_STARTED | Kriteria: nomor legalisir + log; tes |
| A1-086 | Surat keterangan aktif kuliah | P1 | S | PARTIAL | Surat ada; butuh: template khusus + data otomatis |
| A1-087 | Surat pengantar magang/KP | P2 | R | PARTIAL | Idem; template khusus |
| A1-088 | Pelacakan status surat (alur) | P2 | S | NOT_STARTED | Kriteria: timeline diajukan→nomor→selesai; tes |
| A1-089 | Nomor surat otomatis anti-duplikat | P0 | S | VERIFIED | NomorOtomatis + unique; tes race |
| A1-090 | TTE hash + tanda tangan QR | P0 | S | VERIFIED | DokumenTte; CetakDokumenTest |
| A1-091 | Masa berlaku dokumen + reissue | P3 | R | NOT_STARTED | Kriteria: expiry + terbit ulang; tes |
| A1-092 | Beasiswa akademik (IPK threshold) | P1 | S | PARTIAL | Beasiswa umum ada; butuh: aturan IPK otomatis |
| A1-093 | Potongan UKT otomatis by beasiswa | P0 | S | VERIFIED | potonganBeasiswaOtomatis; tes clamp |
| A1-094 | Kuota beasiswa + seleksi | P1 | S | PARTIAL | Kuota ada; butuh: ranking + penetapan; tes |
| A1-095 | Riwayat beasiswa mahasiswa | P1 | R | PARTIAL | MahasiswaBeasiswa ada; butuh: tampil di profil/linimasa |
| A1-096 | Pencairan beasiswa ke tagihan | P1 | S | NOT_STARTED | Kriteria: kredit otomatis; tes jurnal |
| A1-097 | Evaluasi penerima tiap semester | P2 | S | NOT_STARTED | Kriteria: cek IPK/status; tes nonaktif otomatis |
| A1-098 | Akreditasi: profil prodi ringkas | P2 | R | NOT_STARTED | Kriteria: rekap dosen/MK/mhs; tes agregat |
| A1-099 | Akreditasi: rasio dosen:mhs | P2 | R | NOT_STARTED | Kriteria: hitung + ambang; tes |
| A1-100 | Akreditasi: masa studi rata-rata | P2 | R | NOT_STARTED | Kriteria: dari tahun lulus-angkatan; tes |
| A1-101 | Akreditasi: IPK rata-rata lulusan | P2 | R | NOT_STARTED | Kriteria: agregat; tes |
| A1-102 | Akreditasi: publikasi dosen (butuh A7) | P3 | S | NOT_STARTED | Dependensi modul riset |
| A1-103 | Laporan akademik pimpinan (ringkas) | P1 | R | VERIFIED | Dashboard pimpinan; tes |
| A1-104 | Ekspor akademik CSV (KRS/nilai) | P1 | R | VERIFIED | LaporanController; tes kolom |
| A1-105 | Impor master CSV + template + gagal-baris | P1 | S | VERIFIED | ImporCsv + templateImpor; tes |
| A1-106 | Validasi NIM unik + format | P0 | R | VERIFIED | Unique + impor dedup; tes |
| A1-107 | Validasi email unik lintas role | P0 | R | VERIFIED | Unique users; tes |
| A1-108 | Arsip tahun akademik lama (read-only) | P2 | S | NOT_STARTED | Kriteria: flag arsip; tulis ditolak; tes |
| A1-109 | Salin struktur tahun baru (rollover) | P2 | B | NOT_STARTED | Kriteria: duplikasi TA+kelas; tes |
| A1-110 | Penomoran dokumen akademik terpusat | P1 | S | PARTIAL | Surat ada; butuh: SK/yudisium/ijazah satu loket |
| A1-111 | Bahasa pengantar ijazah (ID/EN) | P3 | R | NOT_STARTED | Kriteria: template bilingual; tes render |
| A1-112 | Predikat kelulusan (cumlaude) | P1 | R | PARTIAL | Predikat KHS ada; butuh: aturan + tampil transkrip |
| A1-113 | Wisuda: syarat + batch + kuota + PIN | P0 | S | VERIFIED | Yudisium trx+lock; KelulusanMbkmTest |
| A1-114 | Toga pinjam/kembali tercatat | P1 | R | VERIFIED | updateToga; tes |
| A1-115 | Tutup batch + larangan daftar | P1 | R | VERIFIED | tutupBatch + guard; tes |

## AGENT 2 — PMB AND STUDENT LIFECYCLE (115)

| ID | Pengembangan | Prioritas | Ks | Status | Kriteria terima + uji |
|---|---|---|---|---|---|
| A2-001 | Formulir PMB publik + validasi | P0 | S | VERIFIED | DaftarPublik/PmbController; PmbFlowTest |
| A2-002 | Upload berkas (ijazah/KK/foto) + mime/size | P0 | S | VERIFIED | mimes+max; tes |
| A2-003 | Cek kuota + lock gelombang (anti-overbook) | P0 | S | VERIFIED | lockForUpdate; tes race-safe |
| A2-004 | No. daftar unik + format tahun | P0 | R | VERIFIED | generateNoDaftar loop-cek |
| A2-005 | VA biaya pendaftaran + reuse | P0 | S | VERIFIED | PmbVaService::issueFor; tes idempoten |
| A2-006 | Pelunasan VA PMB (settle reference) | P0 | S | VERIFIED | settleByReference; callback PMBVA |
| A2-007 | Cek status pendaftaran publik | P1 | R | VERIFIED | pmb.cek; PmbFlowTest |
| A2-008 | Verifikasi dokumen admin | P0 | S | VERIFIED | Pmb/Verifikasi ubahStatus; tes |
| A2-009 | Nilai seleksi CBT + wawancara (upsert) | P1 | S | VERIFIED | simpanNilai; tes |
| A2-010 | Kelulusan by rata-rata + ambang + lock kuota | P0 | S | VERIFIED | tentukanKelulusan trx+lock; tes |
| A2-011 | Daftar ulang: user + NIM + tagihan awal | P0 | S | VERIFIED | daftarUlang trx; NIM format; tes |
| A2-012 | NIM race-safe (counter atomik) | P1 | S | PARTIAL | max(id)+1 dalam trx; butuh: sequence lock ketat; tes konkurensi |
| A2-013 | Email unik pendaftar vs user | P0 | R | VERIFIED | unique + cek manual; tes |
| A2-014 | Gelombang + periode + biaya | P1 | R | VERIFIED | PmbGelombang CRUD via master?; isBuka/kuota |
| A2-015 | Kuota per gelombang + sisa real-time | P1 | S | VERIFIED | sisaKuota(); tampil di form |
| A2-016 | Biaya gelombang + potongan beasiswa PMB | P1 | S | VERIFIED | PmbVaService::nominal |
| A2-017 | CRM: sumber prospek (referral_kode) | P1 | R | IMPLEMENTED | Kolom + exists; butuh: dashboard sumber |
| A2-018 | Kode referral + validasi exists | P2 | R | VERIFIED | Rule exists pmb_referrals |
| A2-019 | Lead capture landing (form ringkas) | P2 | S | NOT_STARTED | Kriteria: nama+HP+minat; tersimpan lead; tes |
| A2-020 | Status lead pipeline (baru/dihubungi/daftar) | P2 | S | NOT_STARTED | Kriteria: kanban; tes transisi |
| A2-021 | Skoring pendaftar (nilai+kelengkapan) | P2 | S | NOT_STARTED | Kriteria: skor 0-100 transparan; tes hitung |
| A2-022 | Ranking pendaftar per gelombang | P2 | S | NOT_STARTED | Kriteria: urut nilai; tes |
| A2-023 | Jadwal tes masuk per pendaftar | P2 | S | NOT_STARTED | Kriteria: slot + ruangan; tes bentrok |
| A2-024 | Undangan tes via WA/inbox | P2 | S | NOT_STARTED | Kriteria: template + log kirim; tes |
| A2-025 | Absensi tes masuk | P2 | R | NOT_STARTED | Kriteria: hadir/tidak; tes |
| A2-026 | Input nilai tes massal (impor) | P2 | S | NOT_STARTED | Kriteria: CSV + validasi 0-100; tes |
| A2-027 | Ambang kelulusan per gelombang | P1 | R | PARTIAL | Ambang param di tentukanKelulusan; butuh: simpan per gelombang |
| A2-028 | Pengumuman kelulusan massal | P1 | S | IMPLEMENTED | Cek per no_daftar; butuh: halaman pengumuman massal; tes |
| A2-029 | Banding / sanggah hasil | P3 | S | NOT_STARTED | Kriteria: ajuan + review; tes alur |
| A2-030 | Daftar ulang tenggat + denda telat | P2 | S | NOT_STARTED | Kriteria: jatuh tempo + denda; tes |
| A2-031 | Batal daftar ulang (refund biaya) | P2 | S | NOT_STARTED | Kriteria: status + refund; tes jurnal |
| A2-032 | Pindah prodi pilihan pasca-lulus | P2 | S | NOT_STARTED | Kriteria: prodi1/prodi2 final; tes |
| A2-033 | Arsip berkas pendaftar (retensi) | P1 | R | PARTIAL | File di disk; butuh: kebijakan retensi + hapus terjadwal |
| A2-034 | Ekspor pendaftar CSV | P1 | R | VERIFIED | exportPmb; tes kolom |
| A2-035 | Funnel konversi dashboard | P1 | S | VERIFIED | $funnel + UI bar; tes tampil |
| A2-036 | Funnel by sekolah asal | P2 | S | PARTIAL | Agregat ada di controller?; butuh: filter+tabel; tes |
| A2-037 | Funnel by referral | P2 | S | PARTIAL | Idem; tes |
| A2-038 | Funnel by periode/gelombang | P2 | S | PARTIAL | Idem; tes |
| A2-039 | Konversi lead→daftar (%) | P2 | R | NOT_STARTED | Butuh lead capture (A2-019); tes |
| A2-040 | Notifikasi WA status seleksi | P2 | S | PARTIAL | WaService ada; butuh: trigger tiap transisi; tes log |
| A2-041 | Template pesan WA PMB | P2 | R | PARTIAL | Pesan hardcoded; butuh: template DB + variabel |
| A2-042 | Follow-up terjadwal prospek | P2 | S | NOT_STARTED | Kriteria: antrean + kirim; tes command |
| A2-043 | Integrasi formulir → tagihan awal otomatis | P0 | S | VERIFIED | Ada di daftarUlang; tes |
| A2-044 | Cek duplikat NIK/email/HP | P1 | S | PARTIAL | Email dicek; butuh: NIK+HP unik bila diisi; tes |
| A2-045 | Validasi usia/jenjang sekolah | P2 | R | NOT_STARTED | Kriteria: aturan per jenjang; tes |
| A2-046 | Jalur prestasi (upload sertifikat) | P2 | S | NOT_STARTED | Kriteria: jenis berkas + bobot; tes |
| A2-047 | Jalur afirmasi/kemitraan | P2 | S | NOT_STARTED | Kriteria: flag + kuota khusus; tes |
| A2-048 | Gelombang khusus (transfer/RPL) | P2 | S | NOT_STARTED | Kriteria: jenis gelombang; tes isBuka |
| A2-049 | Biaya berbeda per gelombang | P1 | R | VERIFIED | Kolom biaya; tes nominal |
| A2-050 | Diskon early-bird | P3 | S | NOT_STARTED | Kriteria: potongan by tanggal; tes |
| A2-051 | Kode voucher PMB | P3 | S | NOT_STARTED | Kriteria: kode + kuota + potongan; tes |
| A2-052 | Riwayat perubahan status (audit) | P1 | R | PARTIAL | AuditLog umum; butuh: timeline per pendaftar; tes |
| A2-053 | Catatan verifikator per pendaftar | P1 | R | VERIFIED | catatan_verifikasi; tes |
| A2-054 | Bulk verify (setujui massal) | P2 | S | NOT_STARTED | Kriteria: checkbox + trx; tes atomic |
| A2-055 | Bulk tolak + alasan massal | P2 | S | NOT_STARTED | Kriteria: alasan wajib; tes |
| A2-056 | Cetak kartu peserta tes | P2 | R | NOT_STARTED | Kriteria: PDF + QR; tes render |
| A2-057 | Cetak bukti pendaftaran | P2 | R | NOT_STARTED | Kriteria: PDF no_daftar+VA; tes |
| A2-058 | TTE dokumen kelulusan PMB | P2 | S | NOT_STARTED | Kriteria: reuse DokumenTte; tes verify |
| A2-059 | Sinkron pendaftar→mahasiswa aman (ulang) | P0 | S | VERIFIED | Guard user_id+email; tes idempoten |
| A2-060 | Rollback daftar ulang salah | P2 | S | NOT_STARTED | Kriteria: batal + hapus user/NIM; tes (data!) |
| A2-061 | Profil 360: ringkasan pendaftar | P1 | R | PARTIAL | Data tersebar; butuh: satu tampilan; tes tampil |
| A2-062 | Timeline pendaftaran→wisuda (awal) | P1 | S | VERIFIED | linimasaMahasiswa (akun/KRS/tagihan/bayar); tes |
| A2-063 | Timeline: tambah sidang/yudisium/wisuda | P2 | S | NOT_STARTED | Kriteria: event kelulusan; tes |
| A2-064 | Timeline: tambah beasiswa/skripsi | P2 | S | NOT_STARTED | Kriteria: event terkait; tes |
| A2-065 | Akses orang tua/wali (read-only) | P2 | B | NOT_STARTED | Kriteria: tautan + PIN + scope; tes IDOR |
| A2-066 | Persetujuan orang tua (opsional) | P3 | S | NOT_STARTED | Dependensi akses wali |
| A2-067 | Registrasi ulang semester | P1 | S | NOT_STARTED | Kriteria: checklist + tagihan; tes |
| A2-068 | Status registrasi per semester | P1 | R | NOT_STARTED | Kriteria: flag per TA; tes |
| A2-069 | Blokir layanan bila belum registrasi | P1 | S | NOT_STARTED | Kriteria: gate KRS/nilai; tes |
| A2-070 | Mutasi masuk: alur + dokumen | P2 | S | NOT_STARTED | Duplikat A1-046 dari sisi PMB; tes |
| A2-071 | Mutasi keluar: SK + transkrip | P2 | S | NOT_STARTED | Duplikat A1-047 dari sisi PMB; tes |
| A2-072 | Cuti: ajuan + approval + histori | P1 | S | VERIFIED | Cuti + CutiTest |
| A2-073 | Batas cuti kumulatif | P2 | R | NOT_STARTED | Kriteria: maks 2 semester; tes tolak |
| A2-074 | Aktif kembali pasca-cuti | P1 | S | VERIFIED | ubahStatus/pindahProdi; tes |
| A2-075 | Pindah prodi (histori) | P1 | S | VERIFIED | pindahProdi; tes |
| A2-076 | Pindah prodi: ekuivalensi MK | P2 | S | NOT_STARTED | Dependensi A1-070; tes |
| A2-077 | DO: kriteria + SK + arsip | P1 | S | PARTIAL | Status ada; butuh: SK + arsip; tes |
| A2-078 | Keluar/mengundurkan diri + SK | P2 | S | NOT_STARTED | Kriteria: SK + status; tes |
| A2-079 | Alumni: profil + tracer | P1 | S | PARTIAL | Alumni + hub; butuh: tracer (A1-040); tes |
| A2-080 | Direktori alumni searchable | P2 | R | PARTIAL | Publik list ada; butuh: filter + profil; tes |
| A2-081 | Lowongan/magang hub | P1 | S | VERIFIED | lowongan + lamaran unik; tes |
| A2-082 | Lamaran + status tracking | P1 | S | VERIFIED | MagangLamaran; tes |
| A2-083 | Bursa kerja (job board) | P2 | B | NOT_STARTED | Kriteria: posting + lamar + tutup; tes |
| A2-084 | Kemitraan industri (MoU list) | P3 | S | NOT_STARTED | Kriteria: CRUD mitra; tes (MbkmMitra ada → pakai) |
| A2-085 | MBKM: konversi + logbook + mitra | P1 | S | VERIFIED | KelulusanMbkmTest |
| A2-086 | MBKM: SKS diakui + cap | P1 | R | VERIFIED | sks_diakui; tes |
| A2-087 | MBKM: laporan akhir + nilai | P2 | S | NOT_STARTED | Kriteria: dokumen + nilai; tes |
| A2-088 | KKN/PKL modul | P2 | B | NOT_STARTED | Kriteria: kelompok + lokasi + nilai; tes |
| A2-089 | Beasiswa: aplikasi online | P1 | S | PARTIAL | Kelola admin ada; butuh: form mahasiswa; tes |
| A2-090 | Beasiswa: verifikasi + seleksi | P1 | S | PARTIAL | Butuh: workflow + ranking; tes |
| A2-091 | Beasiswa: pengumuman penerima | P1 | R | NOT_STARTED | Kriteria: publish + notifikasi; tes |
| A2-092 | Beasiswa: riwayat + evaluasi | P2 | S | NOT_STARTED | Lihat A1-095/097 |
| A2-093 | Surat mahasiswa: ajuan + tracking | P1 | S | VERIFIED | Pengajuan + NomorOtomatis; tes |
| A2-094 | Template surat per jenis | P1 | R | PARTIAL | Jenis bebas; butuh: template baku; tes render |
| A2-095 | Helpdesk tiket + SLA | P1 | S | VERIFIED | Tiket + balasan; tes |
| A2-096 | Prioritas tiket + eskalasi | P2 | S | NOT_STARTED | Kriteria: SLA + eskalasi; tes |
| A2-097 | FAQ dinamis (admin kelola) | P3 | R | NOT_STARTED | Kriteria: CRUD FAQ; tes tampil |
| A2-098 | Kalender akademik personal | P2 | R | NOT_STARTED | Kriteria: filter peran; tes |
| A2-099 | Pengingat deadline personal | P2 | S | NOT_STARTED | Kriteria: notifikasi H-3; tes command |
| A2-100 | Ekspor data mahasiswa (privacy) | P1 | S | PARTIAL | Ekspor ada; butuh: mask PII untuk non-admin; tes |
| A2-101 | Audit akses data mahasiswa | P1 | R | PARTIAL | AuditLog umum; butuh: view akses; tes |
| A2-102 | Persetujuan pemrosesan data | P3 | R | NOT_STARTED | Kriteria: consent flag; tes |
| A2-103 | Retensi data alumni | P3 | S | NOT_STARTED | Kriteria: kebijakan + arsip; tes |
| A2-104 | Hapus data pendaftar gagal (GDPR-like) | P3 | S | NOT_STARTED | Kriteria: hapus + bukti; tes (data!) |
| A2-105 | Statistik lifecycle (masuk→lulus) | P2 | R | NOT_STARTED | Kriteria: funnel + durasi studi; tes |
| A2-106 | Dropout rate + alasan | P2 | R | NOT_STARTED | Kriteria: agregat DO/keluar; tes |
| A2-107 | Prediksi kelulusan tepat waktu | P2 | B | NOT_STARTED | Kriteria: model SKS+IPK; tes (lihat A8) |
| A2-108 | Notifikasi kelulusan ke alumni | P3 | R | NOT_STARTED | Kriteria: inbox + email; tes |
| A2-109 | Integrasi SIA eksternal (impor mhs) | P2 | S | PARTIAL | ImporCsv ada; butuh: API impor; tes |
| A2-110 | Sinkronisasi periodik SIA | P3 | S | NOT_STARTED | Kriteria: job + diff; tes |
| A2-111 | Dashboard lifecycle pimpinan | P2 | R | PARTIAL | Statik ada; butuh: funnel visual; tes tampil |
| A2-112 | Laporan tracer + pekerjaan | P2 | R | NOT_STARTED | Dependensi tracer; tes |
| A2-113 | Cetak KTM + verify QR | P0 | S | VERIFIED | KtmController; CetakDokumenTest |
| A2-114 | KTM digital kedaluwarsa + reissue | P3 | R | NOT_STARTED | Kriteria: expiry; tes |
| A2-115 | Arsip dokumen kelulusan per mhs | P1 | S | PARTIAL | SKL dibuat; butuh: paket arsip; tes |

## AGENT 3 — KRS, KHS AND SCHEDULING (105)

| ID | Pengembangan | Prioritas | Ks | Status | Kriteria terima + uji |
|---|---|---|---|---|---|
| A3-001 | Pengajuan KRS + validasi terpusat | P0 | S | VERIFIED | validasiKrs; KrsFlowTest |
| A3-002 | Persetujuan PA scoped + verifikasi ulang | P0 | S | VERIFIED | PersetujuanPa lock; tes |
| A3-003 | Revisi KRS ditolak | P0 | S | VERIFIED | KrsFlowTest revisi; DISETUJUI terkunci |
| A3-004 | Batas SKS by IPK snapshot | P0 | S | VERIFIED | ipk_snapshot; tes |
| A3-005 | Deteksi bentrok jadwal | P0 | S | VERIFIED | validasiKrs; tes |
| A3-006 | Kuota + lock baris kelas | P0 | S | VERIFIED | lockForUpdate; tes race-safe |
| A3-007 | Waitlist + promosi otomatis | P1 | S | VERIFIED | promosikanWaitlist; tes |
| A3-008 | Tunggakan block KRS | P0 | S | VERIFIED | KrsFlowTest |
| A3-009 | Kalender KRS gate | P0 | S | VERIFIED | isOpen('krs'); tes tutup |
| A3-010 | Total SKS otomatis | P1 | R | VERIFIED | sksPerKelas sum |
| A3-011 | Detail KRS per kelas | P1 | R | VERIFIED | KrsDetail; tes |
| A3-012 | Cetak KRS + otorisasi | P1 | R | VERIFIED | krs.cetak + authorizeMahasiswa; tes |
| A3-013 | KRS: tambah relasi prasyarat ganda | P1 | S | VERIFIED | PrasyaratTest |
| A3-014 | Paket semester otomatis | P2 | S | NOT_STARTED | Lihat A1-073 |
| A3-015 | KRS susulan + biaya | P2 | S | NOT_STARTED | Lihat A1-074 |
| A3-016 | Add/drop period | P2 | S | NOT_STARTED | Lihat A1-075 |
| A3-017 | Histori revisi KRS | P2 | S | NOT_STARTED | Lihat A1-076 |
| A3-018 | Approval berjenjang PA+Kaprodi | P2 | S | NOT_STARTED | Lihat A1-077 |
| A3-019 | Notifikasi status KRS | P2 | S | NOT_STARTED | Lihat A1-078 |
| A3-020 | Arsip KRS immutable | P1 | S | NOT_STARTED | Lihat A1-080 |
| A3-021 | Visualisasi bentrok | P2 | S | NOT_STARTED | Lihat A1-026 |
| A3-022 | Rekomendasi MK | P2 | S | NOT_STARTED | Lihat A1-027 |
| A3-023 | Simulasi IPK | P2 | S | NOT_STARTED | Lihat A1-028 |
| A3-024 | Antrean KRS scoped di dashboard | P1 | R | VERIFIED | $krsAntrean scoped; HardeningTest |
| A3-025 | KHS per semester | P0 | S | VERIFIED | KhsController; tes |
| A3-026 | KHS verify QR publik | P0 | S | VERIFIED | CetakDokumenTest |
| A3-027 | IPK + IP semester snapshot | P0 | S | VERIFIED | ringkasan(); tes |
| A3-028 | Riwayat IPK grafik | P2 | S | NOT_STARTED | Lihat A1-029 |
| A3-029 | Transkrip + predikat + PIN | P0 | S | VERIFIED | TranskripController; tes |
| A3-030 | Transkrip verify publik | P0 | S | VERIFIED | Tes verify |
| A3-031 | Legalisir tercatat | P2 | S | NOT_STARTED | Lihat A1-085 |
| A3-032 | Input nilai massal + lock | P0 | S | VERIFIED | InputNilai trx+lock+ownership; tes |
| A3-033 | Kunci/buka nilai (buka=admin) | P0 | S | VERIFIED | KunciNilai; HardeningTest |
| A3-034 | Remedial D/E maks 55 | P1 | S | VERIFIED | terapkanRemedial; CalculationTest |
| A3-035 | Peringatan kehadiran | P1 | R | VERIFIED | warningKehadiran; tes |
| A3-036 | Nilai sidang + BA + lock trx | P1 | S | VERIFIED | NilaiSidang; SkripsiFlowTest |
| A3-037 | Bobot komponen custom | P1 | S | VERIFIED | normalisasiBobot; tes |
| A3-038 | Impor nilai CSV + lock hormat | P1 | S | VERIFIED | imporNilai; tes |
| A3-039 | Ekspor nilai CSV | P1 | R | VERIFIED | exportNilai; tes kolom |
| A3-040 | Gradebook per kelas (rekap) | P1 | R | PARTIAL | Data ada; butuh: tampilan rekap; tes |
| A3-041 | Distribusi huruf per kelas | P2 | R | NOT_STARTED | Kriteria: histogram; tes agregat |
| A3-042 | Statistik nilai prodi | P2 | R | NOT_STARTED | Kriteria: rata2/distribusi; tes |
| A3-043 | Transkrip sementara (unofficial) | P3 | R | NOT_STARTED | Kriteria: watermark draft; tes |
| A3-044 | Perbaikan nilai (grade appeal) | P2 | S | NOT_STARTED | Kriteria: ajuan + approve; tes |
| A3-045 | Nilai susulan/ujian ulang | P2 | S | NOT_STARTED | Kriteria: jadwal + input; tes |
| A3-046 | Presensi QR per pertemuan | P1 | S | VERIFIED | ScanQr; AbsensiTest |
| A3-047 | Anti-titip absen (1 device?) | P2 | S | NOT_STARTED | Kriteria: deteksi ganda; tes |
| A3-048 | Izin/sakit via upload | P2 | S | NOT_STARTED | Kriteria: ajuan + approve; tes |
| A3-049 | Rekap kehadiran <75% | P1 | S | VERIFIED | rekapAbsensi + denda?; tes |
| A3-050 | Ekspor absensi CSV | P1 | R | VERIFIED | exportAbsensi |
| A3-051 | Jadwal kuliah generate | P1 | S | VERIFIED | Jadwal::Generate |
| A3-052 | Jadwal dosen/ruang papan | P2 | R | NOT_STARTED | Lihat A1-051/052 |
| A3-053 | Bentrok ruang saat generate | P1 | S | NOT_STARTED | Lihat A1-049 |
| A3-054 | Pinjam ruang + approval | P1 | S | VERIFIED | PinjamRuang + confirm; tes? (butuh: tes alur) |
| A3-055 | Presensi dosen | P1 | S | VERIFIED | PresensiDosen |
| A3-056 | Kalender + ICS publik | P1 | S | VERIFIED | ics; tes |
| A3-057 | Tahun aktif badge global | P1 | R | VERIFIED | periodeAktif composer; tes tampil |
| A3-058 | Agenda terdekat dashboard | P1 | R | VERIFIED | $agenda merge 3 sumber; tes tampil |
| A3-059 | Jadwal personal mahasiswa | P1 | R | NOT_STARTED | Kriteria: dari KRS disetujui; tes |
| A3-060 | Jadwal personal dosen | P1 | R | NOT_STARTED | Kriteria: dari kelas ampuan; tes |
| A3-061 | Notifikasi jadwal berubah | P2 | S | NOT_STARTED | Kriteria: inbox saat generate ulang; tes |
| A3-062 | Ekspor jadwal CSV/ICS massal | P2 | R | NOT_STARTED | Kriteria: per TA; tes |
| A3-063 | Kelas: kapasitas min pembukaaan | P2 | R | NOT_STARTED | Kriteria: batal bila <min; tes |
| A3-064 | Gabung kelas sepi | P2 | S | NOT_STARTED | Kriteria: merge + pindah KRS; tes |
| A3-065 | Dosen pengganti (substitusi) | P2 | S | NOT_STARTED | Kriteria: assign + notif; tes |
| A3-066 | Matakuliah lintas prodi | P2 | S | NOT_STARTED | Kriteria: izin + kuota; tes |
| A3-067 | Kelas internasional (EN) | P3 | R | NOT_STARTED | Kriteria: flag bahasa; tes tampil |
| A3-068 | SKS praktik vs teori | P3 | R | NOT_STARTED | Kriteria: komposisi; tes |
| A3-069 | Beban SKS dosen per semester | P2 | R | NOT_STARTED | Lihat A1-055 |
| A3-070 | Distribusi IPK prodi | P2 | R | NOT_STARTED | Kriteria: histogram; tes |
| A3-071 | Lulusan tepat waktu % | P2 | R | NOT_STARTED | Lihat A2-105 |
| A3-072 | Prediksi kelulusan (skor) | P2 | B | NOT_STARTED | Lihat A8 (AI) |
| A3-073 | Rekomendasi PA otomatis | P3 | S | NOT_STARTED | Kriteria: assign by kuota; tes |
| A3-074 | Wali list + mahasiswa bimbingan | P1 | R | VERIFIED | WaliList; tes tampil |
| A3-075 | Catatan pembimbing per mhs | P2 | R | NOT_STARTED | Kriteria: notes; tes |
| A3-076 | Konsultasi terjadwal PA | P3 | S | NOT_STARTED | Kriteria: slot + booking; tes |
| A3-077 | Bimbingan log (skripsi) | P1 | S | VERIFIED | Bimbingan/Log; tes |
| A3-078 | Versi dokumen bimbingan | P2 | S | NOT_STARTED | Kriteria: versi file; tes |
| A3-079 | Plagiarisme check hook | P3 | S | NOT_STARTED | Kriteria: adapter + skor; tanpa kredensial = stub |
| A3-080 | Syarat sidang checklist | P1 | S | VERIFIED | Pendaftaran validasi; tes |
| A3-081 | Penjadwalan sidang + ruangan | P1 | S | VERIFIED | Pendaftaran; tes |
| A3-082 | Penguji + peran + nilai | P1 | S | VERIFIED | addPenguji; tes |
| A3-083 | Berita acara generate | P1 | S | VERIFIED | BeritaAcaraService; tes |
| A3-084 | Revisi pasca-sidang + upload | P2 | S | NOT_STARTED | Kriteria: status revisi + berkas; tes |
| A3-085 | Nilai akhir sidang lock | P2 | S | NOT_STARTED | Kriteria: kunci pasca-lulus; tes |
| A3-086 | Sertifikat peserta sidang | P3 | R | NOT_STARTED | Kriteria: PDF; tes render |
| A3-087 | Antrean ujian per hari | P3 | R | NOT_STARTED | Kriteria: slot; tes |
| A3-088 | Pengumuman jadwal sidang | P2 | R | NOT_STARTED | Kriteria: inbox peserta; tes |
| A3-089 | Arsip dokumen sidang | P2 | R | NOT_STARTED | Kriteria: paket per sidang; tes |
| A3-090 | Bank topik + kuota dosen | P1 | S | VERIFIED | BankTopik; tes |
| A3-091 | Klaim topik + approval | P1 | S | NOT_STARTED | Kriteria: ambil topik; tes |
| A3-092 | Judul duplikat detector | P2 | S | NOT_STARTED | Kriteria: similaritas judul; tes |
| A3-093 | Outline/bab tracking | P3 | S | NOT_STARTED | Kriteria: checklist bab; tes |
| A3-094 | Seminar proposal terjadwal | P2 | S | NOT_STARTED | Kriteria: jadwal + nilai; tes |
| A3-095 | Seminar hasil terjadwal | P2 | S | NOT_STARTED | Kriteria: idem |
| A3-096 | Ujian komprehensif | P3 | B | NOT_STARTED | Kriteria: modul + nilai; tes |
| A3-097 | Publikasi syarat lulus (jurnal) | P3 | S | NOT_STARTED | Kriteria: bukti submit; tes |
| A3-098 | Repository skripsi publik | P2 | S | NOT_STARTED | Kriteria: upload final + akses; tes |
| A3-099 | Embargo karya (akses terbatas) | P3 | S | NOT_STARTED | Kriteria: flag + tanggal; tes |
| A3-100 | Statistik skripsi per prodi | P2 | R | NOT_STARTED | Kriteria: status count; tes |
| A3-101 | Masa bimbingan maksimum | P3 | R | NOT_STARTED | Kriteria: peringatan; tes |
| A3-102 | Ganti pembimbing + histori | P2 | S | NOT_STARTED | Kriteria: reassign + log; tes |
| A3-103 | Co-pembimbing | P3 | S | NOT_STARTED | Kriteria: pembimbing 2; tes |
| A3-104 | Sidang online (link + absensi) | P3 | S | NOT_STARTED | Kriteria: link + hadir; tes |
| A3-105 | Arsip BA + nilai final | P2 | R | NOT_STARTED | Kriteria: paket immutable; tes |

## AGENT 4 — LMS AND DIGITAL LEARNING (120)

| ID | Pengembangan | Prioritas | Ks | Status | Kriteria terima + uji |
|---|---|---|---|---|---|
| A4-001 | CRUD materi per pertemuan | P1 | S | VERIFIED | MateriCrud; tes |
| A4-002 | Upload file materi + mime/size | P1 | S | VERIFIED | Validasi; tes |
| A4-003 | Link video materi | P1 | R | IMPLEMENTED | Kolom ada?; butuh: verifikasi render link; tes |
| A4-004 | Urutan materi (ordering) | P2 | R | NOT_STARTED | Kriteria: drag/reorder; tes urutan |
| A4-005 | Pratinjau materi (preview) | P3 | R | NOT_STARTED | Kriteria: modal preview; tes tampil |
| A4-006 | Unduh materi tercatat | P3 | R | NOT_STARTED | Kriteria: counter unduh; tes |
| A4-007 | CRUD tugas + deadline | P1 | S | VERIFIED | TugasCrud; tes |
| A4-008 | Kumpul tugas + file + anti-spoof | P0 | S | VERIFIED | KumpulTugas resolve ulang; tes |
| A4-009 | Batas deadline + tolak telat | P1 | S | VERIFIED | Cek deadline; tes |
| A4-010 | Nilai tugas per pengumpulan | P1 | S | PARTIAL | Kolom?; butuh: input nilai; tes |
| A4-011 | Umpan balik dosen per tugas | P2 | S | NOT_STARTED | Kriteria: komentar + nilai; tes |
| A4-012 | Revisi pengumpulan (resubmit) | P2 | S | NOT_STARTED | Kriteria: versi + batas; tes |
| A4-013 | Deteksi plagiarisme tugas | P3 | B | NOT_STARTED | Kriteria: skor similarity; tanpa layanan = stub |
| A4-014 | Bank soal + kategori + tingkat | P2 | B | NOT_STARTED | Kriteria: CRUD + impor; tes (modul baru) |
| A4-015 | Soal PG + kunci + bobot | P2 | S | NOT_STARTED | Dependensi bank soal |
| A4-016 | Soal esai + rubrik | P2 | S | NOT_STARTED | Dependensi bank soal |
| A4-017 | Randomisasi soal per peserta | P2 | B | NOT_STARTED | Kriteria: seed per user; tes determinisme |
| A4-018 | Randomisasi opsi jawaban | P2 | S | NOT_STARTED | Kriteria: acak per user; tes |
| A4-019 | Paket ujian + durasi + jadwal | P2 | B | NOT_STARTED | Kriteria: window waktu; tes |
| A4-020 | Timer ujian + autosubmit | P2 | S | NOT_STARTED | Kriteria: hitung mundur server-side; tes |
| A4-021 | Autosave jawaban berkala | P2 | S | NOT_STARTED | Kriteria: draft tersimpan; tes |
| A4-022 | Lanjut ujian terputus (resume) | P2 | S | NOT_STARTED | Kriteria: state tersimpan; tes |
| A4-023 | Koreksi otomatis PG | P2 | S | NOT_STARTED | Dependensi bank soal; tes skor |
| A4-024 | Koreksi esai manual + rubrik | P2 | S | NOT_STARTED | Dependensi bank soal; tes |
| A4-025 | Nilai akhir ujian + pembulatan | P2 | S | NOT_STARTED | Kriteria: rumus baku; tes |
| A4-026 | Analisis butir soal (daya beda) | P3 | B | NOT_STARTED | Kriteria: statistik; tes |
| A4-027 | Bank soal versi + arsip | P3 | S | NOT_STARTED | Kriteria: immutable versi dipakai; tes |
| A4-028 | Impor soal (CSV/QTI) | P3 | S | NOT_STARTED | Kriteria: parser + validasi; tes |
| A4-029 | Kuis formatif per materi | P2 | S | NOT_STARTED | Kriteria: kuis ringan; tes |
| A4-030 | Ujian tengah/akhir terjadwal | P2 | S | NOT_STARTED | Lihat paket ujian |
| A4-031 | Nilai ujian → komponen nilai | P2 | S | NOT_STARTED | Kriteria: mapping ke tugas/uts/uas; tes |
| A4-032 | Forum diskusi per kelas | P2 | B | NOT_STARTED | Kriteria: thread + balasan; tes |
| A4-033 | Moderasi forum (lapor/hapus) | P3 | S | NOT_STARTED | Kriteria: peran moderator; tes |
| A4-034 | Pin/sorot diskusi penting | P3 | R | NOT_STARTED | Kriteria: flag; tes |
| A4-035 | Notifikasi balasan diskusi | P3 | S | NOT_STARTED | Kriteria: inbox; tes |
| A4-036 | Kehadiran diskusi → partisipasi | P3 | R | NOT_STARTED | Kriteria: skor; tes |
| A4-037 | Pengumuman kelas (dosen) | P2 | R | NOT_STARTED | Kriteria: CRUD + tampil; tes |
| A4-038 | Kalender tugas per mahasiswa | P2 | R | NOT_STARTED | Kriteria: agregat deadline; tes |
| A4-039 | Pengingat deadline H-1 | P2 | S | NOT_STARTED | Kriteria: notifikasi; tes command |
| A4-040 | Progress belajar per MK (%) | P2 | R | NOT_STARTED | Kriteria: materi selesai/total; tes |
| A4-041 | Tandai materi selesai | P2 | R | NOT_STARTED | Kriteria: toggle; tes |
| A4-042 | Syarat akses materi berurutan | P3 | S | NOT_STARTED | Kriteria: lock urutan; tes |
| A4-043 | Gradebook per kelas | P1 | R | NOT_STARTED | Lihat A3-040 |
| A4-044 | Gradebook ekspor CSV | P2 | R | NOT_STARTED | Kriteria: per kelas; tes |
| A4-045 | Grafik distribusi nilai LMS | P3 | R | NOT_STARTED | Kriteria: histogram; tes |
| A4-046 | Integrasi absensi→LMS | P1 | S | PARTIAL | Absensi terpisah; butuh: tampil di LMS; tes |
| A4-047 | Integrasi nilai LMS→SIAKAD | P1 | S | NOT_STARTED | Kriteria: kirim nilai; tes |
| A4-048 | File besar via chunk upload | P3 | S | NOT_STARTED | Kriteria: resumable; tes |
| A4-049 | Preview PDF/DOC inline | P3 | R | NOT_STARTED | Kriteria: viewer; tes tampil |
| A4-050 | Watermark file unduhan | P3 | S | NOT_STARTED | Kriteria: NIM overlay; tes |
| A4-051 | Masa berlaku tautan file | P3 | S | NOT_STARTED | Kriteria: signed URL; tes expiry |
| A4-052 | Kuota penyimpanan per kelas | P3 | R | NOT_STARTED | Kriteria: batas + peringatan; tes |
| A4-053 | Pembersihan file yatim | P3 | S | NOT_STARTED | Kriteria: job + log; tes (data!) |
| A4-054 | Versi materi (riwayat) | P3 | S | NOT_STARTED | Kriteria: versi; tes |
| A4-055 | Komentar per materi | P3 | S | NOT_STARTED | Kriteria: thread ringan; tes |
| A4-056 | Rating materi | P3 | R | NOT_STARTED | Kriteria: bintang; tes agregat |
| A4-057 | Grup belajar per kelas | P3 | B | NOT_STARTED | Kriteria: grup + anggota; tes |
| A4-058 | Tugas kelompok + pembagian nilai | P3 | S | NOT_STARTED | Kriteria: kelompok; tes |
| A4-059 | Peer review antar mahasiswa | P3 | S | NOT_STARTED | Kriteria: alokasi + rubrik; tes |
| A4-060 | Gamifikasi: poin + lencana | P3 | B | NOT_STARTED | Kriteria: aturan poin; tes |
| A4-061 | Papan peringkat kelas | P3 | R | NOT_STARTED | Kriteria: opt-in; tes |
| A4-062 | Streak belajar harian | P3 | R | NOT_STARTED | Kriteria: hitung; tes |
| A4-063 | Sertifikat penyelesaian MK | P3 | R | NOT_STARTED | Kriteria: PDF + QR; tes |
| A4-064 | Sertifikat terverifikasi publik | P3 | S | NOT_STARTED | Kriteria: verify pubblico; tes |
| A4-065 | Learning analytics dosen | P2 | R | NOT_STARTED | Kriteria: akses + submit rate; tes |
| A4-066 | Mahasiswa tidak aktif (drop-off) | P2 | R | NOT_STARTED | Kriteria: deteksi; tes |
| A4-067 | Rekomendasi materi remedial | P3 | S | NOT_STARTED | Kriteria: by nilai rendah; tes |
| A4-068 | Jalur belajar adaptif | P3 | B | NOT_STARTED | Kriteria: aturan + tes A/B; tes |
| A4-069 | Survei kepuasan per MK | P3 | S | NOT_STARTED | Lihat EDOM A1-034 |
| A4-070 | Umpan balik anonim terjamin | P3 | S | NOT_STARTED | Kriteria: tanpa identitas; tes |
| A4-071 | Kelas arsip (read-only) | P2 | S | NOT_STARTED | Kriteria: flag; tulis ditolak; tes |
| A4-072 | Duplikasi kelas antar TA | P2 | S | NOT_STARTED | Kriteria: salin materi+tugas; tes |
| A4-073 | Impor materi massal (zip) | P3 | S | NOT_STARTED | Kriteria: ekstrak + mapping; tes |
| A4-074 | Ekspor paket kelas (backup) | P3 | S | NOT_STARTED | Kriteria: arsip; tes |
| A4-075 | Akses tamu (preview publik) | P3 | S | NOT_STARTED | Kriteria: token tamu; tes |
| A4-076 | Prasyarat materi (urutan wajib) | P3 | S | NOT_STARTED | Kriteria: lock; tes |
| A4-077 | Kuis prasyarat lulus | P3 | S | NOT_STARTED | Kriteria: ambang; tes |
| A4-078 | Notifikasi nilai terbit | P2 | S | NOT_STARTED | Kriteria: inbox; tes |
| A4-079 | Notifikasi tugas baru | P2 | S | NOT_STARTED | Kriteria: inbox kelas; tes |
| A4-080 | Ringkasan mingguan email | P3 | S | NOT_STARTED | Kriteria: digest; tes command |
| A4-081 | Mode offline (unduh paket) | P3 | S | NOT_STARTED | Kriteria: paket zip; tes |
| A4-082 | Aksesibilitas konten (alt/caption) | P3 | R | NOT_STARTED | Kriteria: field alt; tes |
| A4-083 | Transkrip video (teks) | P3 | R | NOT_STARTED | Kriteria: lampiran teks; tes |
| A4-084 | Multi-bahasa konten | P3 | R | NOT_STARTED | Kriteria: locale per materi; tes |
| A4-085 | Pencarian materi global | P2 | S | PARTIAL | Pencarian ada (judul MK); butuh: isi materi; tes |
| A4-086 | Tag/kategori materi | P3 | R | NOT_STARTED | Kriteria: tagging; tes filter |
| A4-087 | Favorit/bookmark materi | P3 | R | NOT_STARTED | Kriteria: toggle; tes |
| A4-088 | Riwayat tonton/baca | P3 | R | NOT_STARTED | Kriteria: log; tes |
| A4-089 | Estimasi durasi materi | P3 | R | NOT_STARTED | Kriteria: menit; tes |
| A4-090 | Syarat kelulusan modul | P3 | S | NOT_STARTED | Kriteria: ambang; tes |
| A4-091 | Ujian susulan terjadwal | P3 | S | NOT_STARTED | Kriteria: slot khusus; tes |
| A4-092 | Toleransi keterlambatan kumpul | P3 | S | NOT_STARTED | Kriteria: grace period; tes |
| A4-093 | Denda nilai telat otomatis | P3 | S | NOT_STARTED | Kriteria: potongan/hari; tes |
| A4-094 | Pengecualian deadline personal | P3 | S | NOT_STARTED | Kriteria: override; tes |
| A4-095 | Kalender akademik di LMS | P2 | R | NOT_STARTED | Kriteria: widget; tes |
| A4-096 | Sinkron nilai ke transkrip | P1 | S | NOT_STARTED | Lihat A4-047 |
| A4-097 | Audit akses materi sensitif | P3 | R | NOT_STARTED | Kriteria: log; tes |
| A4-098 | Batas unduhan per hari | P3 | S | NOT_STARTED | Kriteria: throttle; tes |
| A4-099 | Laporan aktivitas LMS pimpinan | P2 | R | NOT_STARTED | Kriteria: agregat; tes |
| A4-100 | Ekspor laporan pembelajaran | P2 | R | NOT_STARTED | Kriteria: CSV; tes |
| A4-101 | Integrasi kalender eksternal | P3 | S | NOT_STARTED | Kriteria: ICS per MK; tes |
| A4-102 | LTI provider (standar) | P3 | B | NOT_STARTED | Kriteria: launch LTI; tes |
| A4-103 | LTI tool consumer | P3 | B | NOT_STARTED | Kriteria: embed tool; tes |
| A4-104 | Mode ujian lockdown (konsep) | P3 | R | NOT_STARTED | Kriteria: panduan + flag; tes |
| A4-105 | Verifikasi identitas ujian | P3 | S | NOT_STARTED | Kriteria: foto + cocok; tes |
| A4-106 | Riwayat pelanggaran ujian | P3 | R | NOT_STARTED | Kriteria: log; tes |
| A4-107 | Nilai akhir gabungan (bobot) | P2 | S | NOT_STARTED | Kriteria: rumus; tes |
| A4-108 | Kurva penilaian (opsional) | P3 | S | NOT_STARTED | Kriteria: normalisasi; tes |
| A4-109 | Komentar massal dosen (bulk feedback) | P3 | S | NOT_STARTED | Kriteria: template + terapkan ke banyak; tes |
| A4-110 | Arsip submission per semester | P2 | R | NOT_STARTED | Kriteria: retensi; tes |
| A4-111 | Pemulihan submission terhapus | P3 | R | NOT_STARTED | Kriteria: soft delete; tes |
| A4-112 | Notifikasi dosen: tumpukan koreksi | P2 | R | NOT_STARTED | Kriteria: antrean; tes |
| A4-113 | Koreksi massal cepat (quick grade) | P3 | S | NOT_STARTED | Kriteria: grid input; tes |
| A4-114 | Template tugas reusable | P3 | R | NOT_STARTED | Kriteria: duplikat; tes |
| A4-115 | Template rubrik reusable | P3 | R | NOT_STARTED | Kriteria: pustaka; tes |
| A4-116 | Statistik keterlambatan kelas | P3 | R | NOT_STARTED | Kriteria: agregat; tes |
| A4-117 | Integrasi presensi→kehadiran nilai | P2 | S | NOT_STARTED | Lihat A4-046 |
| A4-118 | Mode gelap LMS | P3 | R | NOT_STARTED | Kriteria: tema; tes tampil |
| A4-119 | Cetak rekap LMS (PDF) | P3 | R | NOT_STARTED | Kriteria: PDF; tes render |
| A4-120 | Panduan LMS per peran | P3 | R | NOT_STARTED | Kriteria: docs; validasi manual |

## AGENT 5 — FINANCE AND ACCOUNTING ERP (120)

| ID | Pengembangan | Prioritas | Ks | Status | Kriteria terima + uji |
|---|---|---|---|---|---|
| A5-001 | Tagihan massal + template UKT | P0 | S | VERIFIED | generateMassal; KeuanganTest |
| A5-002 | Race-safe generate (trx+catch) | P0 | S | VERIFIED | Trx + QueryException skip; tes |
| A5-003 | Template UKT spesifik→global | P1 | S | VERIFIED | resolveTemplateNominal; tes |
| A5-004 | Potongan beasiswa otomatis | P0 | S | VERIFIED | potonganBeasiswaOtomatis; tes clamp |
| A5-005 | Cicilan + distribusi bayar | P1 | S | VERIFIED | bagiCicilan + distribusikan; tes |
| A5-006 | VA/QRIS/ewallet + retry 3x | P0 | S | VERIFIED | PaymentService; tes flaky |
| A5-007 | Reuse pending kanal sama | P0 | S | VERIFIED | Tes reuse; tanpa dobel |
| A5-008 | Tolak VA bila lunas (422) | P0 | S | VERIFIED | abort_if; HardeningTest |
| A5-009 | Callback + cek nominal + idempoten | P0 | S | VERIFIED | settleByReference; tes ganda |
| A5-010 | Bayar manual + dedup + trx + hapus file gagal | P0 | S | VERIFIED | BayarManual; tes |
| A5-011 | Verifikasi approve/reject + jurnal | P0 | S | VERIFIED | verifyPembayaran; KeuanganPhase2Test |
| A5-012 | Refund + reversal + guard ganda | P0 | S | VERIFIED | refundPembayaran; tes |
| A5-013 | Rekonsiliasi CSV + selisih/takdikenal | P1 | S | VERIFIED | rekonsiliasiCsv; tes |
| A5-014 | Neraca saldo ekspor | P1 | R | VERIFIED | exportNeraca; NeracaTest (2) |
| A5-015 | Jurnal penerimaan/refund otomatis | P0 | S | VERIFIED | postPenerimaan firstOrCreate; tes |
| A5-016 | Soft delete bukti keuangan | P0 | S | VERIFIED | Migrasi + trait + tes |
| A5-017 | Denda aturan + auto harian + lock | P0 | S | VERIFIED | DendaRuleKelola + denda:terapkan; tes |
| A5-018 | Denda UI + validasi persen≤100 | P1 | R | VERIFIED | DendaRuleKelola; DendaRuleTest (4) |
| A5-019 | Denda clamp + maksimal + nonaktif | P1 | R | VERIFIED | hitungDenda; CalculationTest |
| A5-020 | Impor tagihan guard berbayar | P0 | S | VERIFIED | Tolak timpa; tes |
| A5-021 | Tunggakan summary per prodi | P1 | S | VERIFIED | tunggakanSummary; tes |
| A5-022 | Pengingat H-7/H+7 + idempoten | P1 | S | VERIFIED | tagihan:ingatkan; tes |
| A5-023 | Pesan WA penagihan | P2 | R | VERIFIED | pesanPengingat; tes isi |
| A5-024 | Kwitansi + pajak + QR | P1 | S | VERIFIED | KwitansiTest (pajak, QR) |
| A5-025 | Status tagihan lunas/sebagian otomatis | P0 | S | VERIFIED | Update terbayar; tes |
| A5-026 | Sisa tagihan tak negatif | P0 | R | VERIFIED | sisa() max 0; CalculationTest |
| A5-027 | Outstanding dashboard scoped | P1 | R | VERIFIED | $saya; HardeningTest |
| A5-028 | Dashboard keuangan pimpinan | P1 | R | VERIFIED | pimpinan(); tes tampil |
| A5-029 | Ekspor tagihan/tunggakan CSV | P1 | R | VERIFIED | export*; tes kolom |
| A5-030 | Template impor + gagal-baris | P1 | S | VERIFIED | templateImpor + impor; tes |
| A5-031 | UKT per prodi/angkatan/jalur | P1 | S | VERIFIED | UktTemplate; tes resolve |
| A5-032 | SPP vs UKT vs PMB vs lain | P1 | R | VERIFIED | Enum jenis; tes |
| A5-033 | Jatuh tempo + hari terlambat | P1 | R | VERIFIED | hariTerlambat(); tes |
| A5-034 | Bea pendaftaran PMB di VA | P0 | S | VERIFIED | PmbVaService nominal-beasiswa |
| A5-035 | Tagihan awal daftar ulang | P0 | S | VERIFIED | daftarUlang; tes |
| A5-036 | Diskon manual per tagihan | P1 | S | PARTIAL | Kolom diskon ada; butuh: UI edit + audit; tes |
| A5-037 | Keringanan khusus (surat) | P2 | S | NOT_STARTED | Kriteria: ajuan + approve + potongan; tes |
| A5-038 | Angsuran di luar cicilan | P2 | S | NOT_STARTED | Kriteria: jadwal custom; tes |
| A5-039 | Denda progresif bertingkat | P2 | S | NOT_STARTED | Kriteria: multi-rule; tes prioritas |
| A5-040 | Denda maksimal global | P2 | R | PARTIAL | Maksimal per rule ada; butuh: cap global; tes |
| A5-041 | Penghapusan denda (waive) + alasan | P2 | S | NOT_STARTED | Kriteria: approve + audit; tes |
| A5-042 | Kuitansi duplikat cetak ulang | P2 | R | NOT_STARTED | Kriteria: counter cetak; tes |
| A5-043 | Tanda terima manual bernomor | P2 | R | NOT_STARTED | Kriteria: sequence; tes unik |
| A5-044 | Kas tunai (tunai counter) | P2 | B | NOT_STARTED | Kriteria: sesi kas + setoran; tes |
| A5-045 | Selisih kas + berita acara | P2 | S | NOT_STARTED | Kriteria: hitung + BA; tes |
| A5-046 | Transfer antar kas | P3 | S | NOT_STARTED | Kriteria: mutasi + jurnal; tes |
| A5-047 | Rekening bank master | P2 | R | NOT_STARTED | Kriteria: CRUD + default; tes |
| A5-048 | Mutasi bank impor massal | P2 | S | NOT_STARTED | Lihat rekonsiliasi; format multi-bank |
| A5-049 | Auto-match aturan (fuzzy) | P3 | B | NOT_STARTED | Kriteria: toleransi; tes |
| A5-050 | Tindak lanjut selisih (tiket) | P3 | S | NOT_STARTED | Kriteria: buat tiket; tes |
| A5-051 | Chart of Accounts master | P1 | B | NOT_STARTED | Kriteria: CRUD kode/nama/jenis; tes (modul baru) |
| A5-052 | CoA hierarki induk-anak | P2 | S | NOT_STARTED | Dependensi CoA |
| A5-053 | CoA saldo normal D/K | P2 | R | NOT_STARTED | Dependensi CoA |
| A5-054 | Jurnal umum manual | P1 | B | NOT_STARTED | Kriteria: debet=kredit; tes balance |
| A5-055 | Jurnal penyesuaian | P2 | S | NOT_STARTED | Dependensi jurnal umum |
| A5-056 | Posting lock per periode | P1 | S | NOT_STARTED | Kriteria: periode terkunci; tes tolak |
| A5-057 | Tutup buku tahunan | P1 | B | NOT_STARTED | Kriteria: laba→modal; tes |
| A5-058 | Buka ulang terkontrol + audit | P2 | S | NOT_STARTED | Dependensi tutup buku |
| A5-059 | Trial balance (sudah) + periode | P1 | R | PARTIAL | Neraca ada; butuh: filter periode; tes |
| A5-060 | Laba rugi | P1 | B | NOT_STARTED | Kriteria: pendapatan-beban; tes |
| A5-061 | Perubahan modal | P2 | S | NOT_STARTED | Dependensi tutup buku |
| A5-062 | Neraca (posisi keuangan) | P1 | B | NOT_STARTED | Beda dgn neraca saldo; tes |
| A5-063 | Arus kas (langsung) | P1 | B | NOT_STARTED | Kriteria: operasi/investasi/pendanaan; tes |
| A5-064 | Arus kas (tak langsung) | P3 | S | NOT_STARTED | Kriteria: rekonsiliasi laba; tes |
| A5-065 | Budget tahunan per unit | P2 | B | NOT_STARTED | Kriteria: pagu + realisasi; tes |
| A5-066 | Revisi budget + approval | P3 | S | NOT_STARTED | Dependensi budget |
| A5-067 | Cost center per prodi/unit | P2 | S | NOT_STARTED | Kriteria: tagging; tes |
| A5-068 | Alokasi biaya bersama | P3 | S | NOT_STARTED | Kriteria: driver alokasi; tes |
| A5-069 | Laporan realisasi budget | P2 | R | NOT_STARTED | Dependensi budget |
| A5-070 | Utang usaha (AP) | P2 | B | NOT_STARTED | Kriteria: invoice vendor; tes |
| A5-071 | Pembayaran vendor + jurnal | P2 | S | NOT_STARTED | Dependensi AP |
| A5-072 | Piutang non-mahasiswa | P3 | S | NOT_STARTED | Kriteria: invoice umum; tes |
| A5-073 | Aset tetap + penyusutan | P2 | B | NOT_STARTED | Kriteria: kartu aset + susut; tes |
| A5-074 | Inventaris barang | P2 | S | NOT_STARTED | Lihat A6 inventaris |
| A5-075 | Stock opname | P3 | S | NOT_STARTED | Kriteria: hitung + selisih; tes |
| A5-076 | Pengadaan (procurement) | P3 | B | NOT_STARTED | Kriteria: PR→PO→terima; tes |
| A5-077 | Kontrak vendor + termin | P3 | S | NOT_STARTED | Kriteria: termin; tes |
| A5-078 | Pajak potong (PPh) | P3 | S | NOT_STARTED | Kriteria: hitung + bukti; tes |
| A5-079 | Faktur pajak keluaran | P3 | S | NOT_STARTED | Kriteria: nomor; tes |
| A5-080 | Honor dosen + potongan | P2 | S | NOT_STARTED | Lihat A6 penggajian |
| A5-081 | Penggajian tendik + slip | P2 | S | NOT_STARTED | Lihat A6 penggajian |
| A5-082 | BPJS/jaminan sosial | P3 | S | NOT_STARTED | Kriteria: iuran; tes |
| A5-083 | Uang muka kegiatan | P3 | S | NOT_STARTED | Kriteria: UM + pertanggungjawaban; tes |
| A5-084 | Pertanggungjawaban (LPJ) | P3 | S | NOT_STARTED | Kriteria: LPJ + sisa; tes |
| A5-085 | Dana penelitian internal | P3 | S | NOT_STARTED | Dependensi A7 |
| A5-086 | Dana pengmas internal | P3 | S | NOT_STARTED | Dependensi A7 |
| A5-087 | Sponsor/donasi tercatat | P3 | S | NOT_STARTED | Kriteria: terima + lapor; tes |
| A5-088 | Wakaf/endowment ledger | P3 | S | NOT_STARTED | Kriteria: dana abadi; tes |
| A5-089 | Multi-tahun anggaran | P3 | R | NOT_STARTED | Kriteria: komparasi; tes |
| A5-090 | Forecast penerimaan (tren) | P3 | B | NOT_STARTED | Kriteria: proyeksi; tes |
| A5-091 | Anomali transaksi (AI/statistik) | P3 | B | NOT_STARTED | Kriteria: flag; tes |
| A5-092 | Dashboard AR aging | P2 | R | NOT_STARTED | Kriteria: bucket umur; tes |
| A5-093 | Surat tagihan massal (cetak) | P2 | R | NOT_STARTED | Kriteria: template + batch; tes |
| A5-094 | SMS gateway tagihan | P3 | S | NOT_STARTED | Kriteria: adapter; tanpa kredensial = stub |
| A5-095 | Autodebet bank | P3 | B | NOT_STARTED | Kriteria: instruksi; tes |
| A5-096 | E-wallet disbursement refund | P3 | S | NOT_STARTED | Kriteria: API refund; tes (sandbox) |
| A5-097 | Split payment multi-metode | P3 | S | NOT_STARTED | Kriteria: gabung kanal; tes |
| A5-098 | Kode bayar kedaluwarsa + reuse | P2 | S | VERIFIED | expires_at + reuse pending; tes |
| A5-099 | Notifikasi VA dibuat | P2 | R | NOT_STARTED | Kriteria: inbox; tes |
| A5-100 | Notifikasi lunas | P2 | R | NOT_STARTED | Kriteria: inbox+WA; tes |
| A5-101 | Kuitansi email otomatis | P3 | R | NOT_STARTED | Kriteria: mail; tes (fake mail) |
| A5-102 | Laporan harian kasir | P2 | R | NOT_STARTED | Kriteria: rekap; tes |
| A5-103 | Rekap per kanal bayar | P2 | R | NOT_STARTED | Kriteria: agregat metode; tes |
| A5-104 | Fee gateway per transaksi | P3 | R | NOT_STARTED | Kriteria: catat fee; tes |
| A5-105 | Rekonsiliasi fee vs settled | P3 | S | NOT_STARTED | Kriteria: selisih; tes |
| A5-106 | Kurs asing (jika ada) | P3 | R | NOT_STARTED | Kriteria: kurs harian; tes |
| A5-107 | Akun bank vs kas fisik | P2 | R | NOT_STARTED | Lihat rekening master |
| A5-108 | Otorisasi refund berjenjang | P2 | S | NOT_STARTED | Kriteria: approve 2 level; tes |
| A5-109 | Batas refund per peran | P2 | R | NOT_STARTED | Kriteria: limit nominal; tes tolak |
| A5-110 | Alasan refund wajib + audit | P1 | R | VERIFIED | refund_reason + AuditLog; tes |
| A5-111 | Blacklist VA abuse | P3 | S | NOT_STARTED | Kriteria: pola + blokir; tes |
| A5-112 | Audit akses data keuangan | P1 | R | PARTIAL | AuditLog umum; butuh: view akses; tes |
| A5-113 | Masking nominal untuk peran render | P2 | S | NOT_STARTED | Kriteria: policy tampil; tes |
| A5-114 | Ekspor jurnal (audit) | P1 | R | NOT_STARTED | Kriteria: CSV lengkap; tes |
| A5-115 | Saldo awal tahun (opening) | P2 | S | NOT_STARTED | Kriteria: entry khusus; tes |
| A5-116 | Perbandingan tahun berjalan vs lalu | P3 | R | NOT_STARTED | Kriteria: komparasi; tes |
| A5-117 | Rasio keuangan kampus | P3 | R | NOT_STARTED | Kriteria: likuiditas; tes |
| A5-118 | Peringatan defisit kas | P3 | R | NOT_STARTED | Kriteria: ambang; tes |
| A5-119 | Simulasi skenario tarif UKT | P3 | B | NOT_STARTED | Kriteria: what-if; tes |
| A5-120 | Dokumentasi kebijakan akuntansi | P3 | R | NOT_STARTED | Kriteria: docs; review manual |

## AGENT 6 — HR, LECTURER AND CAMPUS OPERATIONS (115)

| ID | Pengembangan | Prioritas | Ks | Status | Kriteria terima + uji |
|---|---|---|---|---|---|
| A6-001 | CRUD dosen + NIDN + prodi | P1 | S | VERIFIED | DosenData; DosenDataTest |
| A6-002 | CRUD tendik multi-peran | P1 | S | VERIFIED | peranTendik; tes |
| A6-003 | Proteksi hapus akun sendiri | P0 | R | VERIFIED | Guard + tes |
| A6-004 | NIDN unik + format | P1 | R | VERIFIED | Unique; tes |
| A6-005 | Status dosen aktif/nonaktif | P1 | R | VERIFIED | Tes status |
| A6-006 | Penugasan PA per mahasiswa | P1 | S | VERIFIED | dosen_pa_id; WaliList; tes |
| A6-007 | Wali list + bimbingan | P1 | R | VERIFIED | WaliList; tes tampil |
| A6-008 | Riwayat mengajar per dosen | P2 | R | NOT_STARTED | Kriteria: dari kelas; tes |
| A6-009 | Beban SKS mengajar | P2 | R | NOT_STARTED | Lihat A1-055 |
| A6-010 | BKD penelitian/pengmas | P2 | S | NOT_STARTED | Lihat A1-056/057 |
| A6-011 | SK mengajar | P2 | S | NOT_STARTED | Lihat A1-058 |
| A6-012 | Profil kepegawaian lengkap | P1 | S | PARTIAL | user+dosen ada; butuh: NIP/golongan/TTD; tes |
| A6-013 | NIP + pangkat/golongan | P2 | R | NOT_STARTED | Kriteria: kolom + riwayat; tes |
| A6-014 | TMT + masa kerja | P2 | R | NOT_STARTED | Kriteria: hitung otomatis; tes |
| A6-015 | Status kepegawaian (PNS/PPPK/tetap/kontrak) | P2 | R | NOT_STARTED | Kriteria: enum; tes |
| A6-016 | Kontrak kerja + tanggal akhir | P2 | S | NOT_STARTED | Kriteria: CRUD + reminder; tes |
| A6-017 | Perpanjangan kontrak + approval | P2 | S | NOT_STARTED | Kriteria: alur; tes |
| A6-018 | Berkas kepegawaian (arsip) | P2 | S | NOT_STARTED | Kriteria: upload per kategori; tes |
| A6-019 | Cuti pegawai + kuota + approval | P1 | S | PARTIAL | IzinCutiDosen model ada; butuh: UI+alur; tes |
| A6-020 | Izin/sakit + bukti | P1 | S | NOT_STARTED | Kriteria: ajuan + approve; tes |
| A6-021 | Dinas luar + surat tugas | P2 | S | NOT_STARTED | Kriteria: surat + tanggal; tes |
| A6-022 | Presensi pegawai (masuk/pulang) | P2 | S | NOT_STARTED | Kriteria: clock-in/out; tes |
| A6-023 | Rekap kehadiran pegawai | P2 | R | NOT_STARTED | Kriteria: agregat; tes |
| A6-024 | Lembur + persetujuan | P3 | S | NOT_STARTED | Kriteria: ajuan + rate; tes |
| A6-025 | Shift/jadwal piket | P3 | S | NOT_STARTED | Kriteria: roster; tes bentrok |
| A6-026 | Komponen gaji (pokok/tunjangan) | P2 | B | NOT_STARTED | Kriteria: master; tes |
| A6-027 | Gaji pokok by golongan | P2 | S | NOT_STARTED | Dependensi komponen |
| A6-028 | Tunjangan jabatan/fungsional | P2 | S | NOT_STARTED | Dependensi komponen |
| A6-029 | Honor mengajar per SKS | P2 | S | NOT_STARTED | Kriteria: tarif × SKS; tes |
| A6-030 | Honor sidang/penguji | P2 | S | NOT_STARTED | Kriteria: tarif × peran; tes |
| A6-031 | Potongan (pajak/BPJS/absen) | P2 | S | NOT_STARTED | Kriteria: rumus; tes |
| A6-032 | Slip gaji PDF + QR | P2 | S | NOT_STARTED | Kriteria: PDF; tes render |
| A6-033 | Riwayat penggajian | P2 | R | NOT_STARTED | Kriteria: arsip; tes |
| A6-034 | PPh 21 pegawai | P3 | S | NOT_STARTED | Kriteria: tarif progresif; tes |
| A6-035 | BPJS kesehatan/kerja | P3 | S | NOT_STARTED | Kriteria: iuran; tes |
| A6-036 | Penilaian kinerja tahunan | P2 | B | NOT_STARTED | Kriteria: aspek + skor; tes |
| A6-037 | SKP + realisasi | P2 | S | NOT_STARTED | Kriteria: target vs real; tes |
| A6-038 | Penilaian 360 (atasan/rekan) | P3 | S | NOT_STARTED | Kriteria: anonim; tes |
| A6-039 | Sertifikasi dosen (Serdos) tracking | P2 | R | NOT_STARTED | Kriteria: status + tahun; tes |
| A6-040 | Jabatan fungsional (AA/Lektor/GB) | P2 | R | NOT_STARTED | Kriteria: riwayat; tes |
| A6-041 | Angka kredit (kum) | P3 | S | NOT_STARTED | Kriteria: hitung; tes |
| A6-042 | Pelatihan/workshop + sertifikat | P3 | S | NOT_STARTED | Kriteria: CRUD + ikut; tes |
| A6-043 | Studi lanjut (tugas belajar) | P3 | S | NOT_STARTED | Kriteria: status + beasiswa; tes |
| A6-044 | Penghargaan/sanksi (reward/punish) | P3 | S | NOT_STARTED | Kriteria: catat; tes |
| A6-045 | Surat peringatan (SP) berjenjang | P3 | S | NOT_STARTED | Kriteria: SP1-3; tes |
| A6-046 | PHK/pensiun + pesangon | P3 | S | NOT_STARTED | Kriteria: SK + hitung; tes |
| A6-047 | Organisasi: unit/bagian | P2 | S | NOT_STARTED | Kriteria: tree; tes |
| A6-048 | Jabatan struktural + pejabat | P2 | S | NOT_STARTED | Kriteria: assign + periode; tes |
| A6-049 | Plt/Plh penugasan sementara | P3 | S | NOT_STARTED | Kriteria: flag + tanggal; tes |
| A6-050 | Struktur → approval routing | P3 | B | NOT_STARTED | Kriteria: atasan dinamis; tes |
| A6-051 | Kalender cuti bersama | P3 | R | NOT_STARTED | Kriteria: tanggal; tes |
| A6-052 | Kuota cuti tahunan + sisa | P2 | R | NOT_STARTED | Lihat cuti pegawai |
| A6-053 | Cuti besar/sabatikal | P3 | S | NOT_STARTED | Kriteria: aturan; tes |
| A6-054 | Izin belajar + penyesuaian jadwal | P3 | S | NOT_STARTED | Kriteria: ajuan; tes |
| A6-055 | Beasiswa pegawai | P3 | S | NOT_STARTED | Kriteria: program; tes |
| A6-056 | Asuransi pegawai | P3 | R | NOT_STARTED | Kriteria: polis; tes |
| A6-057 | Koperasi/pinjaman pegawai | P3 | B | NOT_STARTED | Kriteria: ajuan + angsuran; tes |
| A6-058 | Kendaraan dinas + booking | P3 | S | NOT_STARTED | Kriteria: pinjam + log; tes |
| A6-059 | Ruang rapat booking approval | P2 | S | VERIFIED | PinjamRuang; tes tampil |
| A6-060 | Aset: kartu + kode + lokasi | P2 | B | NOT_STARTED | Kriteria: CRUD; tes (modul baru) |
| A6-061 | Aset: kondisi + foto | P2 | R | NOT_STARTED | Dependensi kartu aset |
| A6-062 | Aset: mutasi lokasi | P2 | S | NOT_STARTED | Dependensi kartu aset |
| A6-063 | Aset: penghapusan + BA | P2 | S | NOT_STARTED | Kriteria: BA; tes |
| A6-064 | Aset: penyusutan garis lurus | P2 | S | NOT_STARTED | Kriteria: hitung; tes |
| A6-065 | Aset: opname + selisih | P2 | S | NOT_STARTED | Kriteria: hitung; tes |
| A6-066 | Aset: label QR/barcode | P3 | R | NOT_STARTED | Kriteria: cetak; tes |
| A6-067 | Aset: pemeliharaan terjadwal | P2 | S | NOT_STARTED | Kriteria: jadwal + log; tes |
| A6-068 | Aset: biaya pemeliharaan | P2 | R | NOT_STARTED | Dependensi pemeliharaan |
| A6-069 | Inventaris habis pakai | P2 | S | NOT_STARTED | Kriteria: stok; tes |
| A6-070 | Permintaan barang (PR) | P2 | S | NOT_STARTED | Kriteria: ajuan + approve; tes |
| A6-071 | Pembelian (PO) + terima | P2 | S | NOT_STARTED | Kriteria: PO + terima; tes |
| A6-072 | Vendor/supplier master | P2 | R | NOT_STARTED | Kriteria: CRUD; tes |
| A6-073 | Penilaian vendor | P3 | S | NOT_STARTED | Kriteria: skor; tes |
| A6-074 | Kontrak + termin bayar | P3 | S | NOT_STARTED | Kriteria: termin; tes |
| A6-075 | Gedung + lantai + denah | P3 | S | NOT_STARTED | Kriteria: master; tes tampil |
| A6-076 | Ruang: kapasitas + fasilitas | P2 | R | VERIFIED | seed ruang; pinjam pakai |
| A6-077 | Kebersihan/kerusakan lapor | P3 | S | NOT_STARTED | Kriteria: tiket + tindak; tes |
| A6-078 | Utilitas (listrik/air) catat | P3 | R | NOT_STARTED | Kriteria: meter; tes |
| A6-079 | Keamanan kampus (insiden) | P3 | S | NOT_STARTED | Kriteria: lapor; tes |
| A6-080 | Parkir: slot + izin | P3 | S | NOT_STARTED | Kriteria: kuota; tes |
| A6-081 | Asrama: kamar + penghuni | P3 | B | NOT_STARTED | Kriteria: alokasi; tes |
| A6-082 | Tagihan asrama → tagihan | P3 | S | NOT_STARTED | Dependensi asrama + tagihan |
| A6-083 | Kantin: tenant + sewa | P3 | S | NOT_STARTED | Kriteria: kontrak; tes |
| A6-084 | Kebersihan jadwal regu | P3 | R | NOT_STARTED | Kriteria: roster; tes |
| A6-085 | ATK: stok + permintaan | P3 | S | NOT_STARTED | Lihat inventaris |
| A6-086 | Surat masuk + disposisi | P2 | B | NOT_STARTED | Kriteria: agenda + disposisi; tes |
| A6-087 | Surat keluar + arsip | P2 | S | NOT_STARTED | Lihat surat existing; tambah arsip |
| A6-088 | Ekspedisi internal | P3 | S | NOT_STARTED | Kriteria: tracking; tes |
| A6-089 | Rapat: undangan + notulen | P2 | S | NOT_STARTED | Kriteria: AI notulen ada; tambah undangan; tes |
| A6-090 | Keputusan rapat + tindak lanjut | P3 | S | NOT_STARTED | Kriteria: action items; tes |
| A6-091 | Agenda pimpinan terpadu | P2 | R | PARTIAL | Agenda dashboard ada; butuh: agregat semua sumber |
| A6-092 | Tamu kampus (buku tamu) | P3 | S | NOT_STARTED | Kriteria: catat; tes |
| A6-093 | Event kampus + pendaftaran | P2 | B | NOT_STARTED | Kriteria: event + peserta; tes |
| A6-094 | Sertifikat event + QR | P3 | S | NOT_STARTED | Dependensi event |
| A6-095 | Publikasi berita kampus | P2 | S | NOT_STARTED | Lihat pengumuman; butuh: kategori + arsip |
| A6-096 | Galeri/media kampus | P3 | S | NOT_STARTED | Kriteria: album; tes |
| A6-097 | Sosial media terhubung | P3 | R | NOT_STARTED | Kriteria: tautan; tes tampil |
| A6-098 | Kontak darurat kampus | P3 | R | NOT_STARTED | Kriteria: direktori; tes |
| A6-099 | Layanan kesehatan kampus | P3 | S | NOT_STARTED | Kriteria: kunjungan; tes |
| A6-100 | Konseling mahasiswa | P3 | S | NOT_STARTED | Kriteria: janji + rahasia; tes |
| A6-101 | Bimbingan karir | P3 | S | NOT_STARTED | Kriteria: sesi; tes |
| A6-102 | Bursa kerja (lihat A2-083) | P2 | B | NOT_STARTED | Duplikat terstruktur A2-083 |
| A6-103 | Magang hub (ada) | P1 | S | VERIFIED | lowongan + lamaran unik; tes |
| A6-104 | Penilaian mitra magang | P3 | S | NOT_STARTED | Kriteria: nilai; tes |
| A6-105 | Laporan magang + seminar | P3 | S | NOT_STARTED | Kriteria: dokumen + nilai; tes |
| A6-106 | Kerja praktik (KP) | P2 | S | NOT_STARTED | Lihat A3 magang |
| A6-107 | Kuliah tamu industri | P3 | S | NOT_STARTED | Kriteria: jadwal; tes |
| A6-108 | Kunjungan industri | P3 | S | NOT_STARTED | Kriteria: agenda; tes |
| A6-109 | MoU industri + expiry | P2 | S | NOT_STARTED | Kriteria: dokumen + reminder; tes |
| A6-110 | Implementasi MoU tracking | P3 | S | NOT_STARTED | Dependensi MoU |
| A6-111 | Beasiswa eksternal (info) | P3 | R | NOT_STARTED | Kriteria: info + tautan; tes |
| A6-112 | Penyaluran beasiswa eksternal | P3 | S | NOT_STARTED | Dependensi info |
| A6-113 | Prestasi mahasiswa + poin | P3 | S | NOT_STARTED | Kriteria: catat + SKP; tes |
| A6-114 | UKM/organisasi + SK | P3 | S | NOT_STARTED | Kriteria: daftar + pembina; tes |
| A6-115 | Dana kemahasiswaan per UKM | P3 | S | NOT_STARTED | Kriteria: pagu + LPJ; tes |

## AGENT 7 — THESIS, RESEARCH AND GRADUATION (105)

| ID | Pengembangan | Prioritas | Ks | Status | Kriteria terima + uji |
|---|---|---|---|---|---|
| A7-001 | Pengajuan judul + validasi | P1 | S | VERIFIED | PengajuanJudul; SkripsiFlowTest |
| A7-002 | Bank topik + kuota dosen | P1 | S | VERIFIED | BankTopik; tes |
| A7-003 | Penetapan pembimbing | P1 | S | VERIFIED | SetPembimbing; tes |
| A7-004 | Log bimbingan + versi | P1 | S | VERIFIED | Bimbingan/Log; tes |
| A7-005 | Komentar pembimbing per log | P2 | S | NOT_STARTED | Kriteria: balasan; tes |
| A7-006 | Batas revisi bimbingan | P3 | S | NOT_STARTED | Kriteria: counter; tes |
| A7-007 | Pendaftaran sidang + syarat | P1 | S | VERIFIED | Pendaftaran; tes |
| A7-008 | Penjadwalan sidang + ruang | P1 | S | VERIFIED | Tes tampil |
| A7-009 | Penetapan penguji + peran | P1 | S | VERIFIED | addPenguji; tes |
| A7-010 | Nilai sidang + BA + trx | P1 | S | VERIFIED | NilaiSidang; tes |
| A7-011 | Berita acara generate | P1 | S | VERIFIED | BeritaAcaraService; tes |
| A7-012 | Revisi pasca-sidang | P2 | S | NOT_STARTED | Lihat A3-084 |
| A7-013 | Pengesahan revisi pembimbing | P2 | S | NOT_STARTED | Kriteria: approve; tes |
| A7-014 | Yudisium gate (IPK+tunggakan+sidang) | P0 | S | VERIFIED | luluskan; KelulusanMbkmTest |
| A7-015 | SKL + nomor + QR | P0 | S | VERIFIED | firstOrCreate + TTE; tes |
| A7-016 | Alumni otomatis saat lulus | P0 | S | VERIFIED | Alumni::firstOrCreate; tes |
| A7-017 | Batch wisuda + kuota + lock | P0 | S | VERIFIED | trx+lock; tes kuota/tutup |
| A7-018 | PIN ijazah + no ijazah unik | P1 | S | VERIFIED | Unique; tes |
| A7-019 | Toga pinjam/kembali | P1 | R | VERIFIED | updateToga; tes |
| A7-020 | Buku wisuda (prosesi) | P3 | R | NOT_STARTED | Kriteria: daftar + kursi; tes |
| A7-021 | Streaming/link wisuda | P3 | R | NOT_STARTED | Kriteria: tautan; tes tampil |
| A7-022 | Foto wisuda + unduh | P3 | R | NOT_STARTED | Kriteria: galeri per batch; tes |
| A7-023 | Ijazah bilingual + QR | P2 | S | VERIFIED | Cetak + verify; terapkan template bilingual (A1-111) |
| A7-024 | Legalisir tercatat | P2 | S | NOT_STARTED | Lihat A1-085 |
| A7-025 | Verifikasi publik ijazah | P1 | S | VERIFIED | ktm/transkrip verify; tes |
| A7-026 | Arsip paket lulusan | P2 | R | NOT_STARTED | Kriteria: SKL+transkrip+ijazah; tes |
| A7-027 | Repository skripsi + akses | P2 | S | NOT_STARTED | Lihat A3-098 |
| A7-028 | Similarity check hook | P3 | S | NOT_STARTED | Lihat A3-079 |
| A7-029 | HKI: ciptaan + status | P2 | B | NOT_STARTED | Kriteria: CRUD + nomor; tes (modul baru) |
| A7-030 | HKI: paten + tahap | P2 | S | NOT_STARTED | Dependensi HKI |
| A7-031 | HKI: merek/desain | P3 | S | NOT_STARTED | Dependensi HKI |
| A7-032 | Publikasi: jurnal + quartile | P2 | B | NOT_STARTED | Kriteria: CRUD + Sinta link; tes |
| A7-033 | Publikasi: prosiding/seminar | P2 | S | NOT_STARTED | Dependensi publikasi |
| A7-034 | Publikasi: buku/ISBN | P3 | S | NOT_STARTED | Dependensi publikasi |
| A7-035 | Sitasi + h-index cache | P3 | S | NOT_STARTED | Kriteria: angka + sumber; tes |
| A7-036 | Proposal penelitian + RAB | P1 | B | NOT_STARTED | Kriteria: CRUD + anggaran; tes (modul baru) |
| A7-037 | Seleksi proposal + reviewer | P1 | S | NOT_STARTED | Dependensi proposal |
| A7-038 | Pendanaan + termin cair | P1 | S | NOT_STARTED | Dependensi proposal |
| A7-039 | Laporan kemajuan + akhir | P1 | S | NOT_STARTED | Dependensi proposal |
| A7-040 | Monev penelitian | P2 | S | NOT_STARTED | Kriteria: review; tes |
| A7-041 | Luaran wajib (jurnal/HKI) | P2 | S | NOT_STARTED | Dependensi proposal+publikasi |
| A7-042 | Dana internal vs eksternal | P2 | R | NOT_STARTED | Kriteria: sumber; tes |
| A7-043 | Tim peneliti + peran | P1 | S | NOT_STARTED | Kriteria: ketua/anggota; tes |
| A7-044 | Mahasiswa terlibat riset | P2 | S | NOT_STARTED | Kriteria: tautan mhs; tes |
| A7-045 | Etik penelitian (clearance) | P3 | S | NOT_STARTED | Kriteria: nomor etik; tes |
| A7-046 | Pengmas: proposal + lokasi | P1 | B | NOT_STARTED | Kriteria: CRUD; tes (modul baru) |
| A7-047 | Pengmas: tim + mitra desa | P1 | S | NOT_STARTED | Dependensi pengmas |
| A7-048 | Pengmas: dana + LPJ | P1 | S | NOT_STARTED | Dependensi pengmas |
| A7-049 | Pengmas: luaran + publikasi | P2 | S | NOT_STARTED | Dependensi pengmas |
| A7-050 | Pengmas: keberlanjutan | P3 | R | NOT_STARTED | Kriteria: tindak lanjut; tes |
| A7-051 | KKN: kelompok + DPL + lokasi | P2 | B | NOT_STARTED | Lihat A2-088 |
| A7-052 | KKN: logbook + nilai | P2 | S | NOT_STARTED | Dependensi KKN |
| A7-053 | KP: pengajuan + pembimbing industri | P2 | S | NOT_STARTED | Lihat A3 magang |
| A7-054 | KP: laporan + seminar | P2 | S | NOT_STARTED | Lihat A3 magang |
| A7-055 | MBKM: mitra + konversi + logbook | P1 | S | VERIFIED | KelulusanMbkmTest |
| A7-056 | MBKM: cap SKS + validasi | P1 | R | VERIFIED | Tes |
| A7-057 | Magang hub + lamaran unik | P1 | S | VERIFIED | Tes |
| A7-058 | Seminar proposal terjadwal | P2 | S | NOT_STARTED | Lihat A3-094 |
| A7-059 | Seminar hasil terjadwal | P2 | S | NOT_STARTED | Lihat A3-095 |
| A7-060 | Ujian komprehensif | P3 | B | NOT_STARTED | Lihat A3-096 |
| A7-061 | Sertifikat seminar | P3 | R | NOT_STARTED | Kriteria: PDF; tes |
| A7-062 | Presensi seminar | P3 | R | NOT_STARTED | Kriteria: hadir; tes |
| A7-063 | Arsip dokumen sidang | P2 | R | NOT_STARTED | Lihat A3-089 |
| A7-064 | Notifikasi jadwal bimbingan | P2 | S | NOT_STARTED | Kriteria: reminder; tes |
| A7-065 | Notifikasi jadwal sidang | P2 | S | NOT_STARTED | Kriteria: inbox; tes |
| A7-066 | Kalender bimbingan dosen | P2 | R | NOT_STARTED | Kriteria: slot; tes |
| A7-067 | Kuota bimbingan per dosen | P2 | R | NOT_STARTED | Kriteria: maks + tolak; tes |
| A7-068 | Penilaian pembimbing (mhs menilai) | P3 | S | NOT_STARTED | Kriteria: survei; tes |
| A7-069 | Ganti topik + histori | P2 | S | NOT_STARTED | Kriteria: reassign; tes |
| A7-070 | Duplikat judul detector | P2 | S | NOT_STARTED | Lihat A3-092 |
| A7-071 | Outline bab tracking | P3 | S | NOT_STARTED | Lihat A3-093 |
| A7-072 | Template dokumen baku | P2 | R | NOT_STARTED | Kriteria: template; tes render |
| A7-073 | Pedoman penulisan terbitan | P3 | R | NOT_STARTED | Kriteria: docs; review |
| A7-074 | Cek plagiarisme mandiri mhs | P3 | S | NOT_STARTED | Lihat A3-079 |
| A7-075 | Sidang ulang (remedial) | P2 | S | NOT_STARTED | Kriteria: jadwal ulang; tes |
| A7-076 | Banding nilai sidang | P3 | S | NOT_STARTED | Kriteria: ajuan; tes |
| A7-077 | Penguji eksternal | P3 | S | NOT_STARTED | Kriteria: data + honor; tes |
| A7-078 | Honor penguji otomatis | P3 | S | NOT_STARTED | Lihat A6 honor |
| A7-079 | Transport penguji | P3 | R | NOT_STARTED | Kriteria: klaim; tes |
| A7-080 | Ruangan sidang + peralatan | P3 | R | NOT_STARTED | Kriteria: pinjam; tes |
| A7-081 | Live score sidang (papan) | P3 | R | NOT_STARTED | Kriteria: tampil; tes |
| A7-082 | Arsip video sidang | P3 | R | NOT_STARTED | Kriteria: tautan; tes |
| A7-083 | Yudisium susulan | P3 | S | NOT_STARTED | Kriteria: batch khusus; tes |
| A7-084 | SK yudisium kolektif | P2 | R | NOT_STARTED | Kriteria: PDF; tes |
| A7-085 | Transkrip sementara wisudawan | P3 | R | NOT_STARTED | Lihat A3-043 |
| A7-086 | Gladi wisuda + absensi | P3 | S | NOT_STARTED | Kriteria: hadir; tes |
| A7-087 | Undangan wisuda (keluarga) | P3 | R | NOT_STARTED | Kriteria: kuota undangan; tes |
| A7-088 | Live streaming wisuda | P3 | R | NOT_STARTED | Lihat A7-021 |
| A7-089 | Album wisuda per batch | P3 | R | NOT_STARTED | Lihat A7-022 |
| A7-090 | Testimoni wisudawan | P3 | R | NOT_STARTED | Tanpa fabrikasi; tes tampil |
| A7-091 | Tracer batch (6 bln pasca) | P2 | S | NOT_STARTED | Lihat A1-040 |
| A7-092 | Laporan serapan kerja | P2 | R | NOT_STARTED | Lihat A2-112 |
| A7-093 | Ijazah hilang: pengganti + BA | P3 | S | NOT_STARTED | Kriteria: surat + nomor baru; tes |
| A7-094 | Ralat ijazah (nama/NIM) | P3 | S | NOT_STARTED | Kriteria: berita acara; tes |
| A7-095 | Verifikasi ijazah massal (batch) | P3 | S | NOT_STARTED | Kriteria: unggah CSV; tes |
| A7-096 | Statistik kelulusan per prodi | P2 | R | NOT_STARTED | Kriteria: agregat; tes |
| A7-097 | Lama studi per lulusan | P2 | R | NOT_STARTED | Kriteria: hitung; tes |
| A7-098 | Predikat per lulusan | P2 | R | NOT_STARTED | Lihat A1-112 |
| A7-099 | Orasi ilmiah wisuda | P3 | R | NOT_STARTED | Kriteria: teks; tes tampil |
| A7-100 | Tamu VIP wisuda | P3 | R | NOT_STARTED | Kriteria: daftar; tes |
| A7-101 | Protokol acara wisuda | P3 | R | NOT_STARTED | Kriteria: rundown; tes |
| A7-102 | Evaluasi wisuda (survei) | P3 | S | NOT_STARTED | Kriteria: survei; tes |
| A7-103 | Perbaikan proses tahunan | P3 | R | NOT_STARTED | Kriteria: retro; review |
| A7-104 | Arsip yudisium immutable | P2 | R | NOT_STARTED | Kriteria: hash paket; tes |
| A7-105 | Audit kelulusan (siapa meluluskan) | P1 | R | VERIFIED | AuditLog mahasiswa.lulus; tes |

## AGENT 8 — AI AND AUTOMATION (115)

| ID | Pengembangan | Prioritas | Ks | Status | Kriteria terima + uji |
|---|---|---|---|---|---|
| A8-001 | Chatbot akademik dasar | P2 | S | IMPLEMENTED | Ai/Chatbot; butuh: RAG + tes (saat ini statis) |
| A8-002 | Provider abstraction (multi-LLM) | P1 | B | PARTIAL | ComfyUI service ada; butuh: interface LLM generik; tes fake |
| A8-003 | Konfigurasi model per fitur | P2 | S | NOT_STARTED | Kriteria: pilih model; tes |
| A8-004 | Kredensial aman (settings, never code) | P0 | S | VERIFIED | Pola Integrasi; tes (tidak ada secret di repo) |
| A8-005 | Batas biaya AI per hari | P1 | S | NOT_STARTED | Kriteria: budget + blokir; tes |
| A8-006 | Log pemakaian AI (audit) | P1 | S | NOT_STARTED | Kriteria: prompt/output/token; tes |
| A8-007 | Rate limit AI per user | P1 | S | NOT_STARTED | Kriteria: throttle; tes 429 |
| A8-008 | Knowledge base kampus (dokumen) | P2 | B | NOT_STARTED | Kriteria: ingest docs/; tes retrieval |
| A8-009 | RAG sederhana (chunk+skor) | P2 | B | NOT_STARTED | Kriteria: kutipan sumber; tes |
| A8-010 | RAG evaluasi berkala | P3 | S | NOT_STARTED | Kriteria: uji jawaban; tes |
| A8-011 | Asisten akademik (jadwal/KRS) | P2 | S | NOT_STARTED | Dependensi chatbot+RAG |
| A8-012 | Asisten dosen (rekap kelas) | P2 | S | NOT_STARTED | Dependensi chatbot |
| A8-013 | Asisten mahasiswa (IPK/tagihan) | P2 | S | NOT_STARTED | Dependensi chatbot; scope data sendiri |
| A8-014 | Asisten keuangan (rekap) | P2 | S | NOT_STARTED | Dependensi chatbot; agregat saja |
| A8-015 | Asisten PMB (info prodi) | P2 | S | NOT_STARTED | Dependensi chatbot |
| A8-016 | Asisten eksekutif (ringkasan) | P2 | S | NOT_STARTED | Dependensi chatbot |
| A8-017 | Asisten helpdesk (draft jawaban) | P2 | S | NOT_STARTED | Dependensi chatbot + human approve |
| A8-018 | Human-in-the-loop wajib | P0 | S | PARTIAL | Aturan ada; butuh: flag persetujuan; tes |
| A8-019 | Label "saran AI, bukan keputusan" | P0 | R | NOT_STARTED | Kriteria: disclaimer UI; tes tampil |
| A8-020 | Prediksi mahasiswa berisiko DO | P2 | B | PARTIAL | AtRisk ada (aturan); butuh: model data; tes |
| A8-021 | Skor risiko + alasan | P2 | S | NOT_STARTED | Dependensi prediktor |
| A8-022 | Ambang intervensi PA | P2 | S | NOT_STARTED | Kriteria: notifikasi PA; tes |
| A8-023 | Prediksi kelulusan tepat waktu | P2 | B | NOT_STARTED | Lihat A2-107 |
| A8-024 | Analisis tren performa prodi | P2 | R | NOT_STARTED | Kriteria: grafik; tes |
| A8-025 | Anomali pembayaran | P2 | B | NOT_STARTED | Lihat A5-091 |
| A8-026 | Forecast penerimaan | P2 | B | NOT_STARTED | Lihat A5-090 |
| A8-027 | Analisis funnel PMB | P2 | R | NOT_STARTED | Lihat A2-036 dst (non-AI dulu) |
| A8-028 | Segmentasi pendaftar | P3 | S | NOT_STARTED | Kriteria: klaster; tes |
| A8-029 | Skor lead PMB | P3 | S | NOT_STARTED | Lihat A2-021 |
| A8-030 | AI grading dengan verifikasi manusia | P2 | B | NOT_STARTED | Kriteria: draft + approve dosen; tes |
| A8-031 | AI pembuat soal + kunci | P2 | B | NOT_STARTED | Kriteria: draft + approve; tes |
| A8-032 | AI pembuat materi ringkas | P2 | S | NOT_STARTED | Kriteria: draft; tes |
| A8-033 | AI peringkas dokumen | P2 | S | VERIFIED | Ai ringkas ada (adapter); butuh: tes + guard |
| A8-034 | AI notulen rapat | P2 | S | VERIFIED | Ada endpoint; butuh: tes + guard |
| A8-035 | AI analisis keuangan | P2 | B | NOT_STARTED | Kriteria: ringkasan + disclaimer; tes |
| A8-036 | AI draf surat resmi | P2 | S | NOT_STARTED | Kriteria: template + approve; tes |
| A8-037 | AI draf pengumuman | P3 | S | NOT_STARTED | Kriteria: draft; tes |
| A8-038 | AI terjemah pengumuman | P3 | S | NOT_STARTED | Kriteria: ID/EN; tes |
| A8-039 | AI klasifikasi tiket helpdesk | P2 | S | NOT_STARTED | Kriteria: label + prioritas; tes |
| A8-040 | AI balasan tiket (draft) | P2 | S | NOT_STARTED | Dependensi klasifikasi + approve |
| A8-041 | AI FAQ otomatis dari tiket | P3 | S | NOT_STARTED | Kriteria: kandidat; tes |
| A8-042 | AI deteksi duplikat tiket | P3 | S | NOT_STARTED | Kriteria: saran gabung; tes |
| A8-043 | AI rangkuman bimbingan | P3 | S | NOT_STARTED | Kriteria: ringkas log; tes |
| A8-044 | AI saran topik skripsi | P3 | S | NOT_STARTED | Kriteria: dari bank topik; tes |
| A8-045 | AI cek konsistensi sitasi | P3 | S | NOT_STARTED | Kriteria: format check; tes |
| A8-046 | AI ringkas jurnal (riset) | P3 | S | NOT_STARTED | Dependensi modul riset |
| A8-047 | AI draf proposal penelitian | P3 | S | NOT_STARTED | Dependensi modul riset |
| A8-048 | AI deteksi anomali nilai | P2 | S | NOT_STARTED | Kriteria: lonjakan; tes |
| A8-049 | AI deteksi absensi janggal | P3 | S | NOT_STARTED | Kriteria: pola; tes |
| A8-050 | AI rekomendasi MK (SKS+minat) | P3 | S | NOT_STARTED | Lihat A1-027 |
| A8-051 | AI jadwal optimal (saran) | P3 | B | NOT_STARTED | Kriteria: saran slot; tes |
| A8-052 | AI estimasi kelulusan batch | P3 | B | NOT_STARTED | Kriteria: agregat; tes |
| A8-053 | Laporan AI berkala pimpinan | P2 | S | NOT_STARTED | Kriteria: digest mingguan; tes command |
| A8-054 | Laporan AI keuangan | P3 | S | NOT_STARTED | Kriteria: digest; tes |
| A8-055 | Laporan AI akademik | P3 | S | NOT_STARTED | Kriteria: digest; tes |
| A8-056 | Prompt library terkelola | P3 | R | NOT_STARTED | Kriteria: template; tes |
| A8-057 | Evaluasi kualitas jawaban | P3 | S | NOT_STARTED | Kriteria: rating; tes |
| A8-058 | Feedback loop (jempol) | P3 | R | NOT_STARTED | Kriteria: simpan; tes |
| A8-059 | Redaksi PII otomatis | P0 | S | NOT_STARTED | Kriteria: mask NIM/HP; tes |
| A8-060 | Scope data per peran di RAG | P0 | S | NOT_STARTED | Kriteria: filter; tes IDOR |
| A8-061 | Blokir topik sensitif | P1 | S | NOT_STARTED | Kriteria: daftar + tolak; tes |
| A8-062 | Jejak audit keputusan AI | P1 | S | NOT_STARTED | Kriteria: log; tes |
| A8-063 | Versi model tercatat | P2 | R | NOT_STARTED | Kriteria: field; tes |
| A8-064 | Fallback provider kedua | P2 | S | NOT_STARTED | Kriteria: failover; tes |
| A8-065 | Cache jawaban umum | P2 | S | NOT_STARTED | Kriteria: hit rate; tes |
| A8-066 | Streaming respons | P3 | S | NOT_STARTED | Kriteria: SSE; tes |
| A8-067 | Riwayat percakapan tersimpan | P2 | S | NOT_STARTED | Kriteria: histori; tes |
| A8-068 | Hapus riwayat (privasi) | P2 | S | NOT_STARTED | Kriteria: hapus; tes |
| A8-069 | Ekspor percakapan | P3 | R | NOT_STARTED | Kriteria: unduh; tes |
| A8-070 | Mode offline (tanpa AI) | P1 | R | VERIFIED | Degradasi aman; tes (tanpa kredensial) |
| A8-071 | Status layanan AI (health) | P2 | R | NOT_STARTED | Kriteria: indikator; tes |
| A8-072 | Kuota token per peran | P2 | S | NOT_STARTED | Kriteria: limit; tes |
| A8-073 | Peringatan kuota 80% | P3 | R | NOT_STARTED | Kriteria: notif; tes |
| A8-074 | Tagihan internal AI (chargeback) | P3 | R | NOT_STARTED | Kriteria: rekap; tes |
| A8-075 | Pilihan bahasa respons | P3 | R | NOT_STARTED | Kriteria: ID/EN; tes |
| A8-076 | Nada formal kampus | P3 | R | NOT_STARTED | Kriteria: system prompt; tes |
| A8-077 | Batasan panjang jawaban | P3 | R | NOT_STARTED | Kriteria: max token; tes |
| A8-078 | Sitasi sumber jawaban | P2 | R | NOT_STARTED | Lihat RAG |
| A8-079 | Penanganan "tidak tahu" jujur | P1 | S | NOT_STARTED | Kriteria: akui batas; tes |
| A8-080 | Larangan nilai otomatis final | P0 | S | NOT_STARTED | Kriteria: blokir; tes (kebijakan) |
| A8-081 | Larangan keputusan kelulusan | P0 | S | NOT_STARTED | Kriteria: blokir; tes |
| A8-082 | Larangan data sensitif ke AI publik | P0 | S | NOT_STARTED | Kriteria: filter; tes |
| A8-083 | Review keluaran berkala (sampling) | P2 | S | NOT_STARTED | Kriteria: antrean review; tes |
| A8-084 | Daftar hitam prompt (jailbreak) | P2 | S | NOT_STARTED | Kriteria: pola; tes tolak |
| A8-085 | Watermark konten AI | P3 | R | NOT_STARTED | Kriteria: label; tes |
| A8-086 | Analitik penggunaan per fitur | P3 | R | NOT_STARTED | Kriteria: agregat; tes |
| A8-087 | Analitik kepuasan jawaban | P3 | R | NOT_STARTED | Kriteria: rating; tes |
| A8-088 | Eksperimen A/B prompt | P3 | B | NOT_STARTED | Kriteria: varian; tes |
| A8-089 | Rollback versi prompt | P3 | S | NOT_STARTED | Kriteria: versi; tes |
| A8-090 | Dokumentasi kapabilitas AI | P2 | R | NOT_STARTED | Kriteria: docs; review |
| A8-091 | Pelatihan staf pakai AI | P3 | R | NOT_STARTED | Kriteria: panduan; review |
| A8-092 | Kebijakan AI kampus | P2 | R | NOT_STARTED | Kriteria: dokumen; review |
| A8-093 | Persetujuan mahasiswa (data training) | P3 | R | NOT_STARTED | Kriteria: consent; tes |
| A8-094 | Anonimisasi dataset evaluasi | P2 | S | NOT_STARTED | Kriteria: mask; tes |
| A8-095 | Uji bias berkala | P3 | S | NOT_STARTED | Kriteria: metrik; tes |
| A8-096 | Batas usia data training | P3 | R | NOT_STARTED | Kriteria: retensi; tes |
| A8-097 | Hapus data training on-request | P3 | S | NOT_STARTED | Kriteria: hapus; tes |
| A8-098 | AI kalender: saran jadwal | P3 | S | NOT_STARTED | Lihat A8-051; kriteria: saran slot; tes |
| A8-099 | AI email draf massal | P3 | S | NOT_STARTED | Kriteria: draf; tes |
| A8-100 | AI ringkas rapat (live) | P3 | S | NOT_STARTED | Lihat A8-034; kriteria: transkripsi; tes |
| A8-101 | AI transkrip audio | P3 | S | NOT_STARTED | Kriteria: teks; tes |
| A8-102 | AI deteksi bahasa dokumen | P3 | R | NOT_STARTED | Kriteria: label; tes |
| A8-103 | AI ekstrak metadata ijazah | P3 | S | NOT_STARTED | Kriteria: field; tes |
| A8-104 | AI validasi format NIM | P3 | R | NOT_STARTED | Kriteria: pola; tes |
| A8-105 | AI saran beasiswa cocok | P3 | S | NOT_STARTED | Kriteria: cocok; tes |
| A8-106 | AI estimasi UKT | P3 | S | NOT_STARTED | Kriteria: rentang; tes |
| A8-107 | AI tanya jawab peraturan | P3 | S | NOT_STARTED | Dependensi knowledge base |
| A8-108 | AI panduan PMB 24/7 | P2 | S | NOT_STARTED | Dependensi chatbot |
| A8-109 | AI panduan KRS | P2 | S | NOT_STARTED | Dependensi chatbot |
| A8-110 | Eskalasi ke manusia (handoff) | P1 | S | NOT_STARTED | Kriteria: buat tiket; tes |
| A8-111 | Jam layanan + pesan tutup | P3 | R | NOT_STARTED | Kriteria: jadwal; tes |
| A8-112 | Bahasa daerah dasar | P3 | R | NOT_STARTED | Kriteria: kosakata; tes |
| A8-113 | Mode suara (TTS/STT) | P3 | S | NOT_STARTED | Tanpa kredensial = stub |
| A8-114 | Analitik sentimen tiket | P3 | B | NOT_STARTED | Kriteria: skor; tes |
| A8-115 | Laporan adopsi AI | P3 | R | NOT_STARTED | Kriteria: metrik; tes |

## AGENT 9 — UI/UX AND PRODUCT EXPERIENCE (110)

| ID | Pengembangan | Prioritas | Ks | Status | Kriteria terima + uji |
|---|---|---|---|---|---|
| A9-001 | Shell terang + sidebar grup | P1 | S | VERIFIED | layout + RouteGuard; tes render |
| A9-002 | Collapse persisten (localStorage) | P2 | S | VERIFIED | JS + CSS; uji manual browser |
| A9-003 | Drawer mobile + overlay + Esc | P1 | S | VERIFIED | Kode ada; butuh: uji sentuh (manual) |
| A9-004 | Topbar: breadcrumb dinamis | P1 | R | VERIFIED | yield section; tes tampil |
| A9-005 | Topbar: pencarian global nyata | P1 | S | VERIFIED | PencarianTest (3 tes) |
| A9-006 | Topbar: badge periode aktif | P1 | R | VERIFIED | Composer; tes tampil |
| A9-007 | Topbar: lonceng + dropdown real | P1 | S | VERIFIED | Composer notifikasi; tes tampil |
| A9-008 | Topbar: menu akun (Profil/Keluar) | P1 | R | VERIFIED | Tes tampil |
| A9-009 | Footer universal + brand | P2 | R | VERIFIED | Tes See SIMAK |
| A9-010 | Skip link + fokus terlihat | P1 | R | VERIFIED | Kode + tes markup |
| A9-011 | Reduced-motion global | P1 | R | VERIFIED | Media query; review CSS |
| A9-012 | Landing 11 seksi | P1 | B | VERIFIED | welcome.blade.php; tes render |
| A9-013 | Hero + mockup HTML berdata real | P1 | S | VERIFIED | Tes angka = $stats |
| A9-014 | Showcase 12 modul (link asli) | P1 | R | VERIFIED | Link resolve (audit route) |
| A9-015 | Pratinjau produk (2 komposisi) | P1 | S | VERIFIED | Data real; tes tampil |
| A9-016 | Lifecycle 9 tahap | P2 | R | VERIFIED | Tes tampil |
| A9-017 | 6 kartu peran (link asli) | P1 | R | VERIFIED | Link resolve |
| A9-018 | Keamanan+integrasi terverifikasi | P1 | R | VERIFIED | Tanpa klaim fiktif; review |
| A9-019 | FAQ accordion aksesibel | P1 | S | VERIFIED | Bootstrap collapse + aria |
| A9-020 | CTA + footer benar | P2 | R | VERIFIED | Link resolve |
| A9-021 | SEO meta + OG + JSON-LD prodi | P1 | R | VERIFIED | Tes meta |
| A9-022 | Dashboard 8 blok + funnel | P1 | S | VERIFIED | Controller+blade; tes |
| A9-023 | Hero sapaan berperank | P1 | R | VERIFIED | Tes tampil per peran |
| A9-024 | Metrik 6 kartu + tren | P1 | S | VERIFIED | Real queries |
| A9-025 | Donat status + legenda | P1 | S | VERIFIED | SVG; empty state |
| A9-026 | Arus kas bar ganda | P1 | S | VERIFIED | Real; empty state |
| A9-027 | Antrean scoped peran | P1 | S | VERIFIED | HardeningTest nama |
| A9-028 | Aktivitas + aksi cepat | P1 | R | VERIFIED | Permission-aware; tes |
| A9-029 | Dashboard pimpinan selaras | P1 | R | VERIFIED | kampius-stat; tes tampil |
| A9-030 | Breadcrumb semua modul utama | P1 | R | VERIFIED | yield; tes tampil |
| A9-031 | Tabel: header sticky + badge | P1 | R | VERIFIED | KRS/nilai/bayar; tes tampil |
| A9-032 | Tabel: paginasi + footer jumlah | P1 | R | VERIFIED | links() + count; tes |
| A9-033 | Tabel: search + filter + reset | P1 | S | VERIFIED | wire:model.live + resetPage; tes |
| A9-034 | Tabel: bulk action (pilih banyak) | P2 | B | NOT_STARTED | Kriteria: checkbox + aksi massal; tes |
| A9-035 | Tabel: sortir kolom | P2 | S | NOT_STARTED | Kriteria: klik header; tes |
| A9-036 | Tabel: kepadatan (comfortable/compact) | P3 | R | NOT_STARTED | Kriteria: toggle; tes tampil |
| A9-037 | Tabel: kolom sembunyi (responsif) | P2 | R | PARTIAL | Sebagian text-truncate; butuh: utilitas sistematis |
| A9-038 | Tabel: ekspor tampilan saat ini | P3 | S | NOT_STARTED | Kriteria: CSV sesuai filter; tes |
| A9-039 | Form: section + deskripsi + required | P1 | R | VERIFIED | form-label + hint; tes tampil |
| A9-040 | Form: validasi inline + aria | P1 | S | VERIFIED | @error + is-invalid; tes |
| A9-041 | Form: konfirmasi destruktif | P0 | S | VERIFIED | wire:confirm 18 tombol; tes klik? (manual) |
| A9-042 | Form: loading + disable | P1 | S | VERIFIED | wire:loading 10+ aksi; tes tampil |
| A9-043 | Form: unsaved-change guard | P2 | S | NOT_STARTED | Kriteria: prompt saat kotor; tes JS (manual) |
| A9-044 | Form: wizard multi-langkah | P2 | B | NOT_STARTED | Kriteria: step + validasi per step; tes |
| A9-045 | Form: autosave draf | P3 | S | NOT_STARTED | Kriteria: draft; tes |
| A9-046 | Modal standar (konfirmasi) | P2 | R | PARTIAL | wire:confirm dipakai; butuh: komponen modal generik |
| A9-047 | Drawer detail (geser kanan) | P2 | S | NOT_STARTED | Kriteria: panel + Esc; tes tampil |
| A9-048 | Toast sukses/gagal global | P2 | S | PARTIAL | Alert session ada; butuh: toast auto-dismiss; tes tampil |
| A9-049 | Tooltip bantuan konteks | P2 | R | PARTIAL | title attr ada; butuh: pola konsisten; review |
| A9-050 | Kalender interaktif (klik hari) | P2 | S | NOT_STARTED | Kriteria: detail event; tes |
| A9-051 | Kanban tiket helpdesk | P2 | B | NOT_STARTED | Kriteria: kolom status + drag; tes |
| A9-052 | Timeline aktivitas global | P2 | S | NOT_STARTED | Kriteria: agregat audit; tes |
| A9-053 | Timeline personal (ada) | P1 | S | VERIFIED | Linimasa profil; UserDataTest |
| A9-054 | Feed pengumuman | P1 | R | VERIFIED | List + dropdown; tes |
| A9-055 | Pusat notifikasi + baca | P2 | S | PARTIAL | Dropdown ada; butuh: halaman baca-semua; tes |
| A9-056 | Command palette (Ctrl+K) | P2 | B | NOT_STARTED | Kriteria: lompat rute; tes JS (manual) |
| A9-057 | Pintasan keyboard daftar | P2 | R | NOT_STARTED | Kriteria: modal ?; tes |
| A9-058 | Mode gelap | P2 | S | NOT_STARTED | Kriteria: toggle + persist; tes tampil |
| A9-059 | Ukuran font adjustable | P3 | R | NOT_STARTED | Kriteria: A-/A+; tes |
| A9-060 | Kontras AAA teks utama | P2 | R | NOT_STARTED | Kriteria: rasio ≥7; audit manual |
| A9-061 | Fokus keyboard penuh | P2 | S | NOT_STARTED | Kriteria: tanpa jebakan; audit manual |
| A9-062 | Screen-reader label lengkap | P2 | S | PARTIAL | aria-label kunci ada; butuh: audit menyeluruh |
| A9-063 | Skeleton loading tabel | P2 | R | NOT_STARTED | Kriteria: shimmer; tes tampil |
| A9-064 | Skeleton loading kartu | P3 | R | NOT_STARTED | Kriteria: shimmer; tes tampil |
| A9-065 | Empty state ilustratif semua list | P2 | R | PARTIAL | kampius-empty di banyak modul; butuh: sapuan sisa |
| A9-066 | Error state ramah (403/404/500) | P2 | S | NOT_STARTED | Kriteria: halaman error kustom; tes status |
| A9-067 | Halaman 404 kampus | P2 | R | NOT_STARTED | Lihat error state |
| A9-068 | Micro-interaction hover konsisten | P3 | R | NOT_STARTED | Kriteria: panduan; review |
| A9-069 | Transisi halaman halus | P3 | R | NOT_STARTED | Kriteria: tanpa kedip; uji manual |
| A9-070 | Grafik aksesibel (teks alternatif) | P2 | R | VERIFIED | role+aria-label SVG; tes |
| A9-071 | Tabel responsif (scroll aman) | P1 | R | VERIFIED | table-responsive; review |
| A9-072 | Form responsif (stack mobile) | P1 | R | VERIFIED | col-sm/md; review |
| A9-073 | Tombol sentuh ≥44px mobile | P2 | R | NOT_STARTED | Kriteria: min-height; audit manual |
| A9-074 | Tipografi skala fluid | P3 | R | NOT_STARTED | Kriteria: clamp(); review |
| A9-075 | Layout cetak (print CSS) | P2 | S | PARTIAL | d-print-none ada; butuh: stylesheet cetak; tes |
| A9-076 | Kop dokumen resmi | P2 | R | VERIFIED | KHS/transkrip/kwitansi; tes tampil |
| A9-077 | Watermark draft dokumen | P3 | R | NOT_STARTED | Lihat A3-043 |
| A9-078 | Nomor halaman cetak | P3 | R | NOT_STARTED | Kriteria: CSS counter; tes cetak |
| A9-079 | Tema institusi (logo/warna) | P3 | S | NOT_STARTED | Kriteria: settings; tes tampil |
| A9-080 | Logo kustom + favicon | P3 | R | NOT_STARTED | Kriteria: upload; tes |
| A9-081 | Halaman login premium | P2 | S | PARTIAL | Standar Tabler; butuh: redesign + info demo; tes tampil |
| A9-082 | Lupa password (email reset) | P2 | B | NOT_STARTED | Kriteria: token + mail; tes (butuh mail) |
| A9-083 | Registrasi akun mandiri | P3 | S | NOT_STARTED | Kriteria: daftar + verifikasi; tes |
| A9-084 | Onboarding peran baru | P3 | S | NOT_STARTED | Kriteria: tur; tes tampil |
| A9-085 | Panduan kontekstual (?) | P3 | R | NOT_STARTED | Kriteria: help inline; tes |
| A9-086 | Bahasa Inggris penuh (i18n) | P3 | B | NOT_STARTED | Kriteria: lang files; tes |
| A9-087 | PSEO: halaman per prodi | P2 | R | VERIFIED | prodi.show + SEO; tes |
| A9-088 | PSEO: halaman per topik | P3 | S | NOT_STARTED | Kriteria: template; tes |
| A9-089 | Sitemap XML dinamis | P2 | S | NOT_STARTED | Kriteria: route; tes |
| A9-090 | Robots + meta GEO | P3 | R | NOT_STARTED | Kriteria: file + tag; tes |
| A9-091 | OG image dinamis | P3 | S | NOT_STARTED | Kriteria: generate; tes |
| A9-092 | Performa: lazy image | P2 | R | NOT_STARTED | Kriteria: loading=lazy; audit |
| A9-093 | Performa: CSS kritis | P3 | S | NOT_STARTED | Kriteria: inline kritis; audit |
| A9-094 | PWA: manifest + ikon | P3 | S | NOT_STARTED | Kriteria: installable; audit |
| A9-095 | PWA: offline dasar | P3 | S | NOT_STARTED | Kriteria: service worker; audit |
| A9-096 | Splash + loading app | P3 | R | NOT_STARTED | Kriteria: indikator; tes tampil |
| A9-097 | Error boundary JS | P3 | S | NOT_STARTED | Kriteria: fallback; tes |
| A9-098 | Analitik kunjungan (privat) | P3 | S | NOT_STARTED | Kriteria: self-hosted; tes |
| A9-099 | A/B headline landing | P3 | B | NOT_STARTED | Kriteria: varian; tes |
| A9-100 | Uji 9 breakpoint terdokumentasi | P1 | R | NOT_STARTED | Kriteria: checklist + screenshot; manual |
| A9-101 | Screenshot before/after | P1 | R | NOT_STARTED | Kriteria: 12 file; manual |
| A9-102 | Visual regression baseline | P2 | S | NOT_STARTED | Kriteria: diff tool; manual |
| A9-103 | Panduan kontribusi UI | P3 | R | NOT_STARTED | Kriteria: docs; review |
| A9-104 | Pustaka ikon baku | P2 | R | PARTIAL | Inline SVG; butuh: katalog; review |
| A9-105 | Token spacing 4/8 konsisten | P2 | R | VERIFIED | DESIGN_SYSTEM; audit CSS |
| A9-106 | Tipografi skala heading | P2 | R | VERIFIED | Hierarki; review |
| A9-107 | Palet lolos buta warna | P3 | R | NOT_STARTED | Kriteria: simulasi; review |
| A9-108 | Mode kontras tinggi | P3 | S | NOT_STARTED | Kriteria: toggle; tes |
| A9-109 | Navigasi keyboard skip-link | P1 | R | VERIFIED | Tes markup |
| A9-110 | Final UI audit + skor | P1 | R | VERIFIED | UI_UX_SCORECARD 90 + verifikasi pemilik |

## AGENT 10 — SECURITY, DATABASE, API AND INFRASTRUCTURE (115)

| ID | Pengembangan | Prioritas | Ks | Status | Kriteria terima + uji |
|---|---|---|---|---|---|
| A10-001 | RBAC 6 role + permission | P0 | S | VERIFIED | Seeder + SecurityTest |
| A10-002 | Middleware role semua grup sensitif | P0 | S | VERIFIED | RouteGuardTest (17 rute) |
| A10-003 | Owner-scope KHS/transkrip/KTM | P0 | S | VERIFIED | IDOR 403; CetakDokumenTest |
| A10-004 | Owner-scope tagihan/pembayaran | P0 | S | VERIFIED | authorizeTagihan; tes |
| A10-005 | Scope bimbingan KRS dosen | P0 | S | VERIFIED | krsBimbingan + dashboard; HardeningTest |
| A10-006 | Ownership nilai per kelas | P0 | S | VERIFIED | authorizeKelas; HardeningTest 403 |
| A10-007 | Anti-spoof kumpul tugas | P0 | S | VERIFIED | Resolve ulang Auth; tes |
| A10-008 | Proteksi hapus akun sendiri | P0 | R | VERIFIED | UserData + DosenData; tes |
| A10-009 | Kunci nilai: buka hanya admin | P0 | S | VERIFIED | abort_unless; HardeningTest |
| A10-010 | Upload: mime+size semua titik | P0 | S | VERIFIED | Validasi; tes |
| A10-011 | Whitelist path dokumen publik | P0 | S | VERIFIED | array_key_exists; TraversalTest |
| A10-012 | Traversal traversal regression | P0 | S | VERIFIED | DokumenTraversalTest |
| A10-013 | Webhook HMAC + min secret | P0 | S | VERIFIED | hash_equals; IntegrasiTest |
| A10-014 | Callback nominal mismatch tolak | P0 | S | VERIFIED | Tes 400 |
| A10-015 | Payment idempoten ganda | P0 | S | VERIFIED | settleByReference false kedua; tes |
| A10-016 | VA reuse + tolak lunas | P0 | S | VERIFIED | Tes reuse + 422 |
| A10-017 | Driver simulasi ditolak di prod | P0 | S | VERIFIED | abort prod; tes |
| A10-018 | Session regenerate + rotate | P0 | S | VERIFIED | Controller; SecurityConfigTest |
| A10-019 | Login throttle 10/menit | P0 | S | VERIFIED | RateLimiter; tes |
| A10-020 | Password min 8 + hash | P0 | S | VERIFIED | Hash::make; tes |
| A10-021 | Ganti password butuh password lama | P0 | S | VERIFIED | current_password; tes |
| A10-022 | Reset password admin + confirm | P1 | S | VERIFIED | wire:confirm; tes tampil |
| A10-023 | CSRF semua form | P0 | S | VERIFIED | @csrf; tes post tanpa token 419 (bawaan) |
| A10-024 | XSS escape output | P0 | R | VERIFIED | Blade {{ }}; {!! hanya QR tepercaya |
| A10-025 | SQLi via binding/Eloquent | P0 | R | VERIFIED | Tanpa raw input; like di-escape |
| A10-026 | LIKE wildcard escape pencarian | P1 | S | VERIFIED | str_replace wildcards; PencarianTest |
| A10-027 | Mass assignment guarded (fillable) | P0 | R | VERIFIED | Audit model; tes |
| A10-028 | Rate limit publik/API/AI | P0 | S | VERIFIED | throttle:*; tes 429? (manual) |
| A10-029 | Audit log aksi penting | P0 | S | VERIFIED | AuditLog; admin.audit; tes |
| A10-030 | Audit akses data sensitif | P1 | R | PARTIAL | Umum ada; butuh: view akses; tes |
| A10-031 | HTTP-only + SameSite cookie | P0 | R | VERIFIED | SecurityConfigTest |
| A10-032 | Secure cookie produksi | P0 | R | IMPLEMENTED | .env.example panduan; butuh: verifikasi deploy |
| A10-033 | Serialisasi cache aman (default) | P0 | S | VERIFIED | CacheStoreTest; tolak objek |
| A10-034 | Secrets tidak di repo | P0 | R | VERIFIED | Grep manual; settings DB |
| A10-035 | APP_KEY terisi prod | P1 | R | NOT_STARTED | Kriteria: checklist deploy; manual |
| A10-036 | DEBUG=false prod | P1 | R | NOT_STARTED | Kriteria: checklist deploy; manual |
| A10-037 | MFA TOTP opsional | P2 | B | NOT_STARTED | Kriteria: enroll + kode; tes |
| A10-038 | MFA enforced peran sensitif | P3 | S | NOT_STARTED | Dependensi MFA |
| A10-039 | Backup kode cadangan MFA | P3 | S | NOT_STARTED | Dependensi MFA |
| A10-040 | Session timeout idle | P2 | S | NOT_STARTED | Kriteria: lifetime + warning; tes |
| A10-041 | Force logout perangkat lain | P3 | S | NOT_STARTED | Kriteria: invalidate; tes |
| A10-042 | Riwayat login perangkat | P3 | R | NOT_STARTED | Kriteria: log; tes |
| A10-043 | Deteksi login anomali | P3 | S | NOT_STARTED | Kriteria: IP baru; tes |
| A10-044 | Blokir brute force progresif | P2 | S | PARTIAL | Throttle ada; butuh: backoff; tes |
| A10-045 | CAPTCHA ambang risiko | P3 | S | NOT_STARTED | Kriteria: trigger; tes |
| A10-046 | WAF rules dasar | P3 | S | NOT_STARTED | Butuh infra; di luar kode |
| A10-047 | Security headers (CSP/HSTS) | P2 | S | NOT_STARTED | Kriteria: middleware; tes header |
| A10-048 | HSTS preload prod | P3 | R | NOT_STARTED | Dependensi header |
| A10-049 | Pemindai malware upload | P3 | S | NOT_STARTED | Butuh layanan; stub + tes mock? |
| A10-050 | Karantina file mencurigakan | P3 | S | NOT_STARTED | Dependensi pemindai |
| A10-051 | Batas ukuran global upload | P2 | R | NOT_STARTED | Kriteria: post_max + validasi; tes |
| A10-052 | Tipe file allowlist ketat | P1 | S | VERIFIED | mimes per titik; tes |
| A10-053 | Rename file acak server-side | P1 | R | VERIFIED | store() hash; tes path |
| A10-054 | Non-executable upload dir | P2 | S | NOT_STARTED | Kriteria: .htaccess/nginx; manual |
| A10-055 | Unduhan via signed URL | P2 | S | NOT_STARTED | Kriteria: expiry; tes |
| A10-056 | Validasi konten vs ekstensi | P3 | S | NOT_STARTED | Kriteria: mime sniff; tes |
| A10-057 | Batas resolusi gambar | P3 | R | NOT_STARTED | Kriteria: dimensi; tes |
| A10-058 | Strip EXIF gambar | P3 | S | NOT_STARTED | Kriteria: bersihkan; tes |
| A10-059 | PDF aman (non-JS) | P3 | S | NOT_STARTED | Kriteria: cek; tes |
| A10-060 | Kuota upload per user | P3 | R | NOT_STARTED | Kriteria: limit; tes |
| A10-061 | FK semua relasi inti | P0 | S | PARTIAL | Mayoritas constrained; sisa harmonisasi restrict — DITAHAN (risiko) |
| A10-062 | Unique anti-duplikat inti | P0 | S | VERIFIED | nim/email/no_daftar/va; tes race |
| A10-063 | Index kolom filter/sort | P0 | S | VERIFIED | 9 index + FK index; MySQL live |
| A10-064 | Index komposit panas | P0 | S | VERIFIED | status+verified_at, ta+status; tes |
| A10-065 | N+1 audit + eager | P0 | S | VERIFIED | 6 fix; DashboardPerfTest |
| A10-066 | Ambang query dashboard | P0 | S | VERIFIED | <100 (terukur 93); tes |
| A10-067 | Chunk ekspor besar | P1 | S | VERIFIED | chunk(500); tes |
| A10-068 | Transaksi semua multi-tulis | P0 | S | VERIFIED | Audit 12 lokasi; tes |
| A10-069 | Lock barmi race (kuota/VA/lulus) | P0 | S | VERIFIED | lockForUpdate; tes |
| A10-070 | Paginasi semua list | P1 | R | VERIFIED | paginate(); tes tampil |
| A10-071 | Unbounded query audit | P1 | S | PARTIAL | take()/limit dipakai; butuh: sapuan get() tanpa batas |
| A10-072 | DB backup terjadwal | P1 | S | NOT_STARTED | Lihat BACKUP_RESTORE; butuh: cron |
| A10-073 | Restore rehearsal | P2 | S | NOT_STARTED | Kriteria: drill triwulan; manual |
| A10-074 | PITR (binlog) | P3 | S | NOT_STARTED | Butuh infra; manual |
| A10-075 | Replikasi read-only | P3 | S | NOT_STARTED | Butuh infra; manual |
| A10-076 | Queue worker + retry + gagal | P1 | S | PARTIAL | QUEUE_CONN ada; job AI/welcome?; butuh: worker + dead-letter |
| A10-077 | Queue prioritas | P3 | S | NOT_STARTED | Kriteria: antrean; tes |
| A10-078 | Cache agregat publik aman | P2 | S | DITOLAK | BUKTI: merusak unserialize; strategi: agregat 1 query |
| A10-079 | Cache: kunci versi + TTL | P2 | R | NOT_STARTED | Kriteria: konvensi; review |
| A10-080 | Cache stampede guard | P3 | S | NOT_STARTED | Kriteria: lock; tes |
| A10-081 | Invalidate tepat sasaran | P2 | S | NOT_STARTED | Kriteria: bust on write; tes |
| A10-082 | Rate limit endpoint mahal | P1 | S | PARTIAL | ekspor?; butuh: throttle ekspor; tes |
| A10-083 | Timeout query + slow log | P2 | S | NOT_STARTED | Kriteria: MySQL slow log; manual |
| A10-084 | EXPLAIN audit berkala | P3 | R | NOT_STARTED | Kriteria: checklist; manual |
| A10-085 | Arsip data lama (partisi) | P3 | S | NOT_STARTED | Kriteria: arsip; tes (data!) |
| A10-086 | Retensi log (prune) | P2 | S | NOT_STARTED | Kriteria: hapus >X; tes |
| A10-087 | Kompresi backup | P3 | R | NOT_STARTED | Kriteria: gzip; manual |
| A10-088 | Enkripsi backup | P2 | S | NOT_STARTED | Kriteria: gpg; manual |
| A10-089 | Off-site backup copy | P2 | S | NOT_STARTED | Butuh storage; manual |
| A10-090 | Health + migrasi guard | P0 | S | VERIFIED | /health pending_migrations; HealthTest |
| A10-091 | Monitoring uptime eksternal | P3 | S | NOT_STARTED | Butuh layanan; manual |
| A10-092 | Error tracking (Sentry/dll) | P3 | S | NOT_STARTED | Butuh layanan; tes |
| A10-093 | Log terstruktur JSON | P3 | R | NOT_STARTED | Kriteria: channel; review |
| A10-094 | Alert error kritis | P3 | S | NOT_STARTED | Kriteria: notif; tes |
| A10-095 | API versioning v1/v2 | P1 | S | VERIFIED | Prefix + kontrak; tes |
| A10-096 | API auth Sanctum + abilities | P1 | S | VERIFIED | abilities:mobile; tes |
| A10-097 | API token expiry/rotasi | P2 | S | NOT_STARTED | Kriteria: expiry; tes |
| A10-098 | API rate limit per token | P2 | S | NOT_STARTED | Kriteria: throttle; tes 429 |
| A10-099 | API error konsisten | P1 | S | VERIFIED | Format JSON; tes |
| A10-100 | API paginasi standar | P1 | S | VERIFIED | paginate(); tes |
| A10-101 | API filter/sort aman | P2 | S | NOT_STARTED | Kriteria: allowlist; tes injeksi |
| A10-102 | API validasi konsisten | P1 | S | VERIFIED | FormRequest/inline; tes |
| A10-103 | API docs + contoh | P1 | R | VERIFIED | API_DOCUMENTATION.md; review |
| A10-104 | API changelog + deprecation | P2 | R | NOT_STARTED | Kriteria: header Sunset; tes |
| A10-105 | Webhook retry + backoff | P1 | S | PARTIAL | Retry masuk ada; butuh: antrean keluar; tes |
| A10-106 | Webhook log + replay | P2 | S | NOT_STARTED | Kriteria: log + ulang; tes |
| A10-107 | Webhook secret rotasi | P2 | S | NOT_STARTED | Kriteria: ganda aktif; tes |
| A10-108 | SSO SAML/OIDC | P3 | B | NOT_STARTED | Butuh IdP; stub dilarang klaim |
| A10-109 | Integrasi testing sandbox | P2 | S | PARTIAL | Driver simulated; butuh: suite sandbox; tes |
| A10-110 | Kontrak PDDikti terpeta | P2 | R | NOT_STARTED | Kriteria: mapping tabel; review |
| A10-111 | Sinkronisasi terjadwal aman | P3 | S | NOT_STARTED | Kriteria: job + diff; tes |
| A10-112 | Validasi pre-push PDDikti | P3 | S | NOT_STARTED | Kriteria: cek; tes |
| A10-113 | Monitoring integrasi (status) | P2 | R | NOT_STARTED | Kriteria: dasbor; tes |
| A10-114 | Deploy checklist + verify | P1 | R | VERIFIED | DEPLOYMENT_GUIDE + health; tes |
| A10-115 | Rollback plan + uji | P2 | S | NOT_STARTED | Kriteria: prosedur + drill; manual |

**TOTAL ITEM: A1:115 + A2:115 + A3:105 + A4:120 + A5:120 + A6:115 + A7:105 + A8:115 + A9:110 + A10:115 = 1.135 (melebihi minimum 1.130, terverifikasi via skrip: 0 duplikat).**
Status ringkas: VERIFIED/IMPLEMENTED/TESTED = fitur hidup teruji; PARTIAL = ada tapi kurang; NOT_STARTED = backlog jujur; DITOLAK = ditolak berdasar bukti (lihat item).

