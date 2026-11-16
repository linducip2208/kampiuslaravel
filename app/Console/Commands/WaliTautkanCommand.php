<?php

namespace App\Console\Commands;

use App\Models\Mahasiswa;
use App\Models\User;
use App\Models\WaliMahasiswa;
use Illuminate\Console\Command;

/**
 * Ide bank: tautkan akun orangtua/wali ke mahasiswa (basis API read-only).
 */
class WaliTautkanCommand extends Command
{
    protected $signature = 'wali:tautkan {user : ID atau email akun wali} {mahasiswa : ID atau NIM mahasiswa}';

    protected $description = 'Tautkan akun wali ke mahasiswa untuk API orangtua.';

    public function handle(): int
    {
        $userArg = (string) $this->argument('user');
        $user = is_numeric($userArg)
            ? User::find((int) $userArg)
            : User::where('email', $userArg)->first();

        $mhsArg = (string) $this->argument('mahasiswa');
        $mhs = is_numeric($mhsArg)
            ? Mahasiswa::find((int) $mhsArg)
            : Mahasiswa::where('nim', $mhsArg)->first();

        if (! $user || ! $mhs) {
            $this->error('Akun wali atau mahasiswa tidak ditemukan.');

            return self::FAILURE;
        }

        WaliMahasiswa::firstOrCreate(['user_id' => $user->id, 'mahasiswa_id' => $mhs->id]);

        $this->info("Wali {$user->email} ditautkan ke NIM {$mhs->nim}.");

        return self::SUCCESS;
    }
}
