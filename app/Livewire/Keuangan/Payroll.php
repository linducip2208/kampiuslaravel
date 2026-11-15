<?php

namespace App\Livewire\Keuangan;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\PayrollPeriode;
use App\Models\PayrollSlip;
use App\Models\User;
use App\Services\PayrollService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-038: penggajian — periode + slip + kunci final.
 */
class Payroll extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['keuangan', 'kasir', 'akuntansi', 'hrd', 'superadmin', 'admin_prodi'];

    public ?int $periodeId = null;

    public string $periodeBaru = '';

    public ?int $user_id = null;

    public int $gaji_pokok = 0;

    public int $tunjangan = 0;

    public int $potongan = 0;

    public string $message = '';

    public string $cariPegawai = '';

    public function buatPeriode(): void
    {
        $this->validate([
            'periodeBaru' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/', 'unique:payroll_periode,periode'],
        ]);

        $p = PayrollPeriode::create(['periode' => $this->periodeBaru]);
        $this->periodeId = $p->id;
        $this->reset('periodeBaru');
        $this->message = 'Periode dibuat.';
    }

    public function simpanSlip(): void
    {
        abort_unless($this->periodeId, 422);

        $this->validate([
            'user_id' => 'required|exists:users,id',
            'gaji_pokok' => 'required|integer|min:0',
            'tunjangan' => 'required|integer|min:0',
            'potongan' => 'required|integer|min:0',
        ]);

        try {
            PayrollService::simpanSlip($this->periodeId, $this->user_id, $this->gaji_pokok, $this->tunjangan, $this->potongan);
        } catch (\RuntimeException $e) {
            $this->addError('user_id', $e->getMessage());

            return;
        }

        $this->reset(['user_id', 'gaji_pokok', 'tunjangan', 'potongan']);
        $this->message = 'Slip tersimpan.';
    }

    public function kunci(): void
    {
        abort_unless($this->periodeId, 422);
        PayrollService::kunciPeriode($this->periodeId);
        $this->message = 'Periode dikunci (final).';
    }

    public function render()
    {
        $this->periodeId ??= PayrollPeriode::orderByDesc('id')->value('id');

        return view('livewire.keuangan.payroll', [
            'periodes' => PayrollPeriode::orderByDesc('id')->get(),
            'slips' => $this->periodeId
                ? PayrollSlip::with('pegawai')->where('periode_id', $this->periodeId)->latest('id')->paginate(10)
                : null,
            'total' => $this->periodeId ? PayrollService::totalPeriode($this->periodeId) : 0,
            'pegawais' => User::where('name', 'like', '%'.$this->cariPegawai.'%')->orderBy('name')->take(50)->get(),
            'periode' => $this->periodeId ? PayrollPeriode::find($this->periodeId) : null,
        ]);
    }
}
