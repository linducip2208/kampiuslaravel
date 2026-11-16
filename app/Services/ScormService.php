<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Materi;
use App\Models\Pertemuan;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * M-030/031: paket offline per pertemuan — ZIP berisi berkas materi +
 * imsmanifest.xml (SCORM 1.2 minimal, importable LMS lain) + metadata.json.
 * Unduhan dihitung 1x per request (tanpa state unduhan per user).
 */
class ScormService
{
    public static function paket(int $pertemuanId): string
    {
        $pertemuan = Pertemuan::with(['materi', 'kelas.matakuliah'])->findOrFail($pertemuanId);

        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Ekstensi PHP zip tidak tersedia.');
        }

        $dir = 'exports/scorm';
        Storage::disk('local')->makeDirectory($dir);
        $nama = sprintf('paket-pertemuan-%d-%s.zip', $pertemuan->id, now()->format('Ymd-His'));
        $path = Storage::disk('local')->path("{$dir}/{$nama}");

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat arsip ZIP.');
        }

        $sumber = [];
        $i = 0;

        foreach ($pertemuan->materi as $materi) {
            $i++;
            $id = 'RES-'.$i;

            if ($materi->file_path && Storage::disk('public')->exists($materi->file_path)) {
                $ekst = pathinfo($materi->file_path, PATHINFO_EXTENSION);
                $namaBerkas = "files/materi-{$materi->id}.".($ekst !== '' ? $ekst : 'bin');
                $zip->addFromString($namaBerkas, (string) Storage::disk('public')->get($materi->file_path));
            } else {
                $namaBerkas = "files/materi-{$materi->id}.txt";
                $zip->addFromString($namaBerkas, ($materi->judul ?? 'Materi')."\n".($materi->deskripsi ?? '')."\n".($materi->link ?? ''));
            }

            $sumber[] = ['id' => $id, 'href' => $namaBerkas, 'judul' => (string) ($materi->judul ?? 'Materi '.$i)];
        }

        $zip->addFromString('imsmanifest.xml', self::manifest($pertemuan, $sumber));
        $zip->addFromString('metadata.json', (string) json_encode([
            'pertemuan_id' => $pertemuan->id,
            'kelas' => $pertemuan->kelas->nama ?? null,
            'matakuliah' => $pertemuan->kelas->matakuliah->nama ?? null,
            'diekspor_at' => now()->toIso8601String(),
            'jumlah_materi' => count($sumber),
        ], JSON_PRETTY_PRINT));
        $zip->close();

        return $path;
    }

    /**
     * Paket massal per kelas (semua pertemuan): struktur folder per
     * pertemuan + imsmanifest.xml gabungan + metadata.json.
     */
    public static function paketKelas(int $kelasId): string
    {
        $kelas = Kelas::with('matakuliah')->findOrFail($kelasId);
        $pertemuans = Pertemuan::with('materi')->where('kelas_id', $kelasId)->orderBy('ke')->get();

        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Ekstensi PHP zip tidak tersedia.');
        }

        $dir = 'exports/scorm';
        Storage::disk('local')->makeDirectory($dir);
        $nama = sprintf('paket-kelas-%d-%s.zip', $kelas->id, now()->format('Ymd-His'));
        $path = Storage::disk('local')->path("{$dir}/{$nama}");

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat arsip ZIP.');
        }

        $sumber = [];
        $i = 0;

        foreach ($pertemuans as $pertemuan) {
            foreach ($pertemuan->materi as $materi) {
                $i++;
                $id = 'RES-'.$i;
                $folder = "pertemuan-{$pertemuan->ke}/";

                if ($materi->file_path && Storage::disk('public')->exists($materi->file_path)) {
                    $ekst = pathinfo($materi->file_path, PATHINFO_EXTENSION);
                    $namaBerkas = $folder."materi-{$materi->id}.".($ekst !== '' ? $ekst : 'bin');
                    $zip->addFromString($namaBerkas, (string) Storage::disk('public')->get($materi->file_path));
                } else {
                    $namaBerkas = $folder."materi-{$materi->id}.txt";
                    $zip->addFromString($namaBerkas, ($materi->judul ?? 'Materi')."\n".($materi->deskripsi ?? '')."\n".($materi->link ?? ''));
                }

                $sumber[] = ['id' => $id, 'href' => $namaBerkas, 'judul' => (string) ("P{$pertemuan->ke}: ".($materi->judul ?? 'Materi '.$i))];
            }
        }

        // Manifest minimal per pertemuan agar tetap importable sebagai satu paket.
        $zip->addFromString('imsmanifest.xml', self::manifestKelas($kelas, $sumber));
        $zip->addFromString('metadata.json', (string) json_encode([
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->nama ?? null,
            'matakuliah' => $kelas->matakuliah->nama ?? null,
            'diekspor_at' => now()->toIso8601String(),
            'jumlah_pertemuan' => $pertemuans->count(),
            'jumlah_materi' => count($sumber),
        ], JSON_PRETTY_PRINT));
        $zip->close();

        return $path;
    }

    /**
     * @param  list<array{id: string, href: string, judul: string}>  $sumber
     */
    private static function manifestKelas(Kelas $kelas, array $sumber): string
    {
        $esc = fn (string $s): string => htmlspecialchars($s, ENT_XML1);

        $items = '';
        $resources = '';

        foreach ($sumber as $s) {
            $items .= sprintf('<item identifier="ITEM-%s" identifierref="%s"><title>%s</title></item>', $esc($s['id']), $esc($s['id']), $esc($s['judul']));
            $resources .= sprintf('<resource identifier="%s" type="webcontent" href="%s"><file href="%s"/></resource>', $esc($s['id']), $esc($s['href']), $esc($s['href']));
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<manifest identifier="KAMPUS-KELAS-'.$kelas->id.'" version="1.2" '
            .'xmlns="http://www.imsproject.org/xsd/imscp_rootv1p1p2" '
            .'xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_rootv1p2" '
            .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'."\n"
            .'  <organizations default="ORG-1"><organization identifier="ORG-1">'
            .'<title>'.$esc('Kelas '.($kelas->nama ?? $kelas->id)).'</title>'.$items.'</organization></organizations>'."\n"
            .'  <resources>'.$resources.'</resources>'."\n"
            .'</manifest>';
    }

    /**
     * @param  list<array{id: string, href: string, judul: string}>  $sumber
     */
    private static function manifest(Pertemuan $pertemuan, array $sumber): string
    {
        $esc = fn (string $s): string => htmlspecialchars($s, ENT_XML1);

        $items = '';
        $resources = '';

        foreach ($sumber as $s) {
            $items .= sprintf('<item identifier="ITEM-%s" identifierref="%s"><title>%s</title></item>', $esc($s['id']), $esc($s['id']), $esc($s['judul']));
            $resources .= sprintf('<resource identifier="%s" type="webcontent" href="%s"><file href="%s"/></resource>', $esc($s['id']), $esc($s['href']), $esc($s['href']));
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<manifest identifier="KAMPUS-'.$pertemuan->id.'" version="1.2" '
            .'xmlns="http://www.imsproject.org/xsd/imscp_rootv1p1p2" '
            .'xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_rootv1p2" '
            .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'."\n"
            .'  <organizations default="ORG-1"><organization identifier="ORG-1">'
            .'<title>'.$esc('Pertemuan '.$pertemuan->id).'</title>'.$items.'</organization></organizations>'."\n"
            .'  <resources>'.$resources.'</resources>'."\n"
            .'</manifest>';
    }
}
