<?php

namespace App\Livewire\Lms;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\BankSoal as BankSoalModel;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Impor CSV bank soal per mata kuliah.
 * Format baris: pertanyaan,jenis,opsi,kunci,bobot (delimiter , atau ; otomatis).
 * Opsi dipisah | (maks 6). Baris header dilewati.
 */
class BankSoalImpor extends Component
{
    use RequiresRoles;
    use WithFileUploads;

    public array $allowedRoles = ['dosen', 'kaprodi', 'kajur', 'superadmin', 'admin_prodi'];

    public ?int $matakuliahId = null;

    public $fileCsv;

    public string $message = '';

    /** @var array{masuk: int, gagal: int, errors: list<string>} */
    public array $hasil = ['masuk' => 0, 'gagal' => 0, 'errors' => []];

    public function mount(?int $matakuliahId = null): void
    {
        $this->matakuliahId = $matakuliahId;
    }

    public function impor(): void
    {
        $this->validate([
            'matakuliahId' => 'required|exists:matakuliah,id',
            'fileCsv' => 'required|file|max:2048|mimes:csv,txt',
        ]);

        $content = (string) file_get_contents($this->fileCsv->getRealPath());

        $masuk = 0;
        $errors = [];
        $barisNomor = 0;

        foreach (preg_split('/\r\n|\r|\n/', $content) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $barisNomor++;

            $delimiter = str_contains($line, ';') && ! str_contains($line, ',') ? ';' : ',';
            $cols = str_getcsv($line, $delimiter);
            $cols = array_pad(array_map(fn ($c) => trim((string) $c), $cols), 5, '');

            // Lewati baris header.
            if ($barisNomor === 1 && (mb_stripos($cols[0], 'pertanyaan') !== false || mb_strtolower($cols[1]) === 'jenis')) {
                continue;
            }

            [$pertanyaan, $jenis, $opsiRaw, $kunciRaw, $bobotRaw] = $cols;

            $gagal = $this->validasiBaris($pertanyaan, $jenis, $opsiRaw, $kunciRaw, $bobotRaw);

            if ($gagal !== null) {
                $errors[] = "Baris {$barisNomor}: {$gagal}";

                continue;
            }

            $jenis = mb_strtolower($jenis);
            $opsi = array_values(array_filter(array_map('trim', explode('|', $opsiRaw)), fn ($o) => $o !== ''));
            $kunci = mb_strtoupper(trim($kunciRaw));

            BankSoalModel::create([
                'matakuliah_id' => $this->matakuliahId,
                'jenis' => $jenis,
                'pertanyaan' => $pertanyaan,
                'opsi' => $jenis === 'pg' ? $opsi : null,
                'kunci' => $kunci !== '' ? $kunci : null,
                'bobot' => $bobotRaw !== '' ? (int) $bobotRaw : 1,
                'aktif' => true,
            ]);

            $masuk++;
        }

        $this->hasil = ['masuk' => $masuk, 'gagal' => count($errors), 'errors' => array_slice($errors, 0, 50)];
        $this->message = "Impor selesai: {$masuk} masuk, ".count($errors).' gagal.';
        $this->reset('fileCsv');
    }

    private function validasiBaris(string $pertanyaan, string $jenis, string $opsiRaw, string $kunciRaw, string $bobotRaw): ?string
    {
        if ($pertanyaan === '') {
            return 'pertanyaan wajib diisi.';
        }

        if (mb_strlen($pertanyaan) > 2000) {
            return 'pertanyaan maksimal 2000 karakter.';
        }

        $jenis = mb_strtolower(trim($jenis));

        if (! in_array($jenis, ['pg', 'esai'], true)) {
            return 'jenis harus pg atau esai.';
        }

        if ($bobotRaw !== '' && (! ctype_digit($bobotRaw) || (int) $bobotRaw < 1 || (int) $bobotRaw > 100)) {
            return 'bobot harus angka 1-100.';
        }

        $opsi = array_values(array_filter(array_map('trim', explode('|', $opsiRaw)), fn ($o) => $o !== ''));
        $kunci = mb_strtoupper(trim($kunciRaw));

        if ($jenis === 'pg') {
            if (count($opsi) < 2) {
                return 'soal pilihan ganda butuh minimal 2 opsi dipisah |.';
            }

            if (count($opsi) > 6) {
                return 'opsi maksimal 6.';
            }

            if ($kunci === '' || ! in_array($kunci, ['A', 'B', 'C', 'D', 'E', 'F'], true)) {
                return 'kunci harus A-F sesuai urutan opsi.';
            }

            if ((ord($kunci) - ord('A')) >= count($opsi)) {
                return "kunci {$kunci} melebihi jumlah opsi (".count($opsi).').';
            }
        } elseif ($kunci !== '') {
            return 'soal esai tanpa kunci — kosongkan kolom kunci.';
        }

        return null;
    }

    public function render(): View
    {
        return view('livewire.lms.bank-soal-impor');
    }
}
