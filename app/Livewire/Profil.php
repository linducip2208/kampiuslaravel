<?php

namespace App\Livewire;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profil extends Component
{
    use RequiresRoles;

    /** @var list<string> */
    public array $allowedRoles = [];

    public string $name = '';

    public string $email = '';

    public string $passwordSaatIni = '';

    public string $passwordBaru = '';

    public string $passwordBaruConfirmation = '';

    public string $messageProfil = '';

    public string $messagePassword = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
    }

    public function simpanProfil(): void
    {
        $user = Auth::user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update(['name' => $this->name, 'email' => $this->email]);

        $this->messageProfil = 'Profil tersimpan.';
        $this->messagePassword = '';
    }

    public function gantiPassword(): void
    {
        $this->validate([
            'passwordSaatIni' => ['required', 'current_password'],
            'passwordBaru' => ['required', 'string', 'min:8', 'confirmed:passwordBaruConfirmation'],
        ], [], [
            'passwordSaatIni' => 'password saat ini',
            'passwordBaru' => 'password baru',
        ]);

        Auth::user()->update(['password' => Hash::make($this->passwordBaru)]);
        $this->reset(['passwordSaatIni', 'passwordBaru', 'passwordBaruConfirmation']);
        $this->messagePassword = 'Password berhasil diganti.';
        $this->messageProfil = '';
    }

    public function render()
    {
        return view('livewire.profil', [
            'linimasa' => $this->linimasaMahasiswa(),
        ]);
    }

    /**
     * Linimasa siklus mahasiswa pemilik akun (KRS, tagihan, pembayaran).
     * Kosong untuk non-mahasiswa. Semua query owner-scope + guarded.
     *
     * @return Collection<int, array{tanggal: ?string, judul: string, sub: string}>
     */
    public function linimasaMahasiswa(): Collection
    {
        $user = Auth::user();
        if (! $user || ! $user->hasRole('mahasiswa') || ! Schema::hasTable('mahasiswa')) {
            return collect();
        }

        $mhsId = Mahasiswa::where('user_id', $user->id)->value('id');
        if (! $mhsId) {
            return collect();
        }

        $items = collect();
        $items->push([
            'tanggal' => optional($user->created_at)->format('d M Y'),
            'judul' => 'Akun terdaftar',
            'sub' => $user->email,
        ]);

        if (Schema::hasTable('krs')) {
            foreach (Krs::where('mahasiswa_id', $mhsId)->latest('id')->take(8)->get() as $krs) {
                $items->push([
                    'tanggal' => optional($krs->created_at)->format('d M Y'),
                    'judul' => 'KRS '.$krs->status,
                    'sub' => ((int) $krs->total_sks).' SKS',
                ]);
            }
        }

        if (Schema::hasTable('tagihan')) {
            foreach (Tagihan::where('mahasiswa_id', $mhsId)->latest('id')->take(8)->get() as $t) {
                $items->push([
                    'tanggal' => optional($t->created_at)->format('d M Y'),
                    'judul' => 'Tagihan '.$t->jenis.' '.$t->status,
                    'sub' => 'Rp '.number_format((int) $t->nominal, 0, ',', '.'),
                ]);
            }
        }

        if (Schema::hasTable('pembayaran')) {
            $tagihanIds = Schema::hasTable('tagihan')
                ? Tagihan::where('mahasiswa_id', $mhsId)->pluck('id')
                : collect();
            if ($tagihanIds->isNotEmpty()) {
                foreach (Pembayaran::whereIn('tagihan_id', $tagihanIds)->latest('id')->take(8)->get() as $b) {
                    $items->push([
                        'tanggal' => optional($b->verified_at ?? $b->created_at)->format('d M Y'),
                        'judul' => 'Pembayaran '.$b->status,
                        'sub' => 'Rp '.number_format((int) $b->nominal, 0, ',', '.'),
                    ]);
                }
            }
        }

        return $items->filter(fn ($i) => $i['tanggal'] !== null)->values();
    }
}
