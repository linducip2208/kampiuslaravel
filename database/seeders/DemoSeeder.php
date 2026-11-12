<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\Bimbingan;
use App\Models\JurnalDosen;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\Materi;
use App\Models\Nilai;
use App\Models\Notifikasi;
use App\Models\Pembayaran;
use App\Models\Pengumuman;
use App\Models\Pertemuan;
use App\Models\Quiz;
use App\Models\Sidang;
use App\Models\SkripsiJudul;
use App\Models\Surat;
use App\Models\Tagihan;
use App\Models\Tugas;
use App\Models\TugasKumpul;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data demo end-to-end: dosen@ & mahasiswa@kampus.ac.id bisa klik semua modul.
 * Idempotent (firstOrCreate / updateOrCreate) — aman dijalankan ulang.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $dosenUser = User::where('email', 'dosen@kampus.ac.id')->first();
        $mhsUser = User::where('email', 'mahasiswa@kampus.ac.id')->first();

        if (! $dosenUser || ! $mhsUser) {
            $this->command->warn('DemoSeeder: akun demo belum ada, jalankan MasterDataSeeder dulu.');

            return;
        }

        $dosen = $dosenUser->dosen;
        $mhs = $mhsUser->mahasiswa;
        $kelas = Kelas::first();
        $tahunId = $kelas?->tahun_akademik_id;

        if (! $dosen || ! $mhs || ! $kelas) {
            $this->command->warn('DemoSeeder: dosen/mahasiswa/kelas belum ada.');

            return;
        }

        // --- Pertemuan 1-3 (ke-1 QR aktif sekarang) ---
        for ($ke = 1; $ke <= 3; $ke++) {
            Pertemuan::firstOrCreate(
                ['kelas_id' => $kelas->id, 'ke' => $ke],
                [
                    'tanggal' => now()->subDays(3 - $ke)->toDateString(),
                    'topik' => "Pertemuan {$ke} — Pengantar & Praktik",
                    'qr_token' => $ke === 1 ? 'DEMO-QR-001' : 'DEMO-QR-00'.$ke,
                    'qr_expires_at' => $ke === 1 ? now()->addDay() : now()->subDay(),
                    'window_mulai' => now()->subHour(),
                    'window_selesai' => now()->addHours(2),
                ]
            );
        }
        $p1 = Pertemuan::where('kelas_id', $kelas->id)->where('ke', 1)->first();

        Materi::firstOrCreate(
            ['pertemuan_id' => $p1->id, 'judul' => 'Slide Pengantar'],
            ['link' => 'https://example.ac.id/materi/pengantar', 'deskripsi' => 'Materi demo']
        );

        $tugas = Tugas::firstOrCreate(
            ['kelas_id' => $kelas->id, 'judul' => 'Tugas 1 — Studi Kasus'],
            ['pertemuan_id' => $p1->id, 'deskripsi' => 'Kerjakan studi kasus bab 1', 'deadline' => now()->addDays(5)]
        );

        TugasKumpul::firstOrCreate(
            ['tugas_id' => $tugas->id, 'mahasiswa_id' => $mhs->id],
            ['file_path' => null, 'nilai' => 85]
        );

        Quiz::firstOrCreate(
            ['kelas_id' => $kelas->id, 'judul' => 'Kuis 1'],
            ['durasi_menit' => 30, 'deadline' => now()->addDays(3)]
        );

        Absensi::firstOrCreate(
            ['pertemuan_id' => $p1->id, 'mahasiswa_id' => $mhs->id],
            ['status' => 'hadir', 'scanned_at' => now()]
        );

        JurnalDosen::firstOrCreate(
            ['pertemuan_id' => $p1->id],
            ['realisasi' => 'Materi tersampaikan 100%', 'kendala' => '-']
        );

        // --- KRS disetujui ---
        $krs = Krs::firstOrCreate(
            ['mahasiswa_id' => $mhs->id, 'tahun_akademik_id' => $tahunId],
            ['status' => Krs::STATUS_DISETUJUI, 'total_sks' => $kelas->matakuliah->sks ?? 3]
        );
        KrsDetail::firstOrCreate(
            ['krs_id' => $krs->id, 'kelas_id' => $kelas->id],
            ['sks' => $kelas->matakuliah->sks ?? 3, 'status' => 'disetujui']
        );

        // --- Nilai (NA = .3*85+.3*80+.3*88+.1*100 = 85.9 → A) ---
        Nilai::updateOrCreate(
            ['mahasiswa_id' => $mhs->id, 'kelas_id' => $kelas->id],
            ['tugas' => 85, 'uts' => 80, 'uas' => 88, 'kehadiran' => 100, 'na' => 85.90, 'huruf' => 'A', 'bobot' => 4.0, 'is_locked' => true]
        );

        // --- Keuangan: 1 lunas + 1 menunggak ---
        $lunas = Tagihan::firstOrCreate(
            ['mahasiswa_id' => $mhs->id, 'tahun_akademik_id' => $tahunId, 'jenis' => 'UKT'],
            ['nominal' => 5000000, 'terbayar' => 5000000, 'status' => 'lunas']
        );
        Pembayaran::firstOrCreate(
            ['reference' => 'DEMO-LUNAS-001'],
            ['tagihan_id' => $lunas->id, 'va_number' => '888800000001', 'metode' => 'va_simulated', 'nominal' => 5000000, 'status' => 'verified', 'verified_at' => now()]
        );
        Tagihan::firstOrCreate(
            ['mahasiswa_id' => $mhs->id, 'tahun_akademik_id' => $tahunId, 'jenis' => 'SPP'],
            ['nominal' => 1500000, 'terbayar' => 0, 'status' => 'belum_bayar']
        );

        // --- Skripsi + bimbingan + sidang ---
        $judul = SkripsiJudul::firstOrCreate(
            ['mahasiswa_id' => $mhs->id, 'judul' => 'Sistem Informasi Akademik Terpadu Berbasis Web'],
            ['deskripsi' => 'Demo', 'status' => 'disetujui', 'pembimbing1_id' => $dosen->id]
        );
        Bimbingan::firstOrCreate(
            ['judul_id' => $judul->id, 'dosen_id' => $dosen->id, 'catatan' => 'Bab 1 disetujui, lanjut Bab 2'],
            ['status' => 'disetujui', 'tanggal' => now()->subDays(2)->toDateString()]
        );
        Sidang::firstOrCreate(
            ['judul_id' => $judul->id, 'jenis' => 'proposal'],
            ['tanggal' => now()->addWeek()->toDateString(), 'status' => 'terdaftar']
        );

        // --- Surat + pengumuman + notifikasi ---
        Surat::firstOrCreate(
            ['mahasiswa_id' => $mhs->id, 'jenis' => 'aktif_kuliah'],
            ['keperluan' => 'Pengajuan beasiswa', 'nomor_surat' => 'AKT/UNIV/'.now()->year.'/0001', 'status' => 'selesai']
        );
        Pengumuman::firstOrCreate(
            ['judul' => 'Kalender Akademik Semester Berjalan'],
            ['isi' => "Perkuliahan berjalan normal.\nUTS minggu ke-8.", 'target' => 'semua', 'published_at' => now()]
        );
        Pengumuman::firstOrCreate(
            ['judul' => 'Batas Akhir KRS'],
            ['prodi_id' => $mhs->prodi_id, 'isi' => 'KRS ditutup akhir minggu ini.', 'target' => 'mahasiswa', 'published_at' => now()]
        );
        Notifikasi::firstOrCreate(
            ['user_id' => $mhsUser->id, 'judul' => 'KRS Disetujui'],
            ['isi' => 'KRS kamu telah disetujui Dosen PA.']
        );

        $this->command->info('DemoSeeder: data demo lengkap.');
    }
}
