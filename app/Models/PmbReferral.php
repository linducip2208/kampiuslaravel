<?php

namespace App\Models;

use Database\Factories\PmbReferralFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Referral sekolah untuk marketing PMB.
 *
 * @property int $id
 * @property string $kode
 * @property string $sekolah
 * @property string|null $pic_nama
 * @property string|null $pic_hp
 * @property bool $aktif
 */
class PmbReferral extends Model
{
    /** @use HasFactory<PmbReferralFactory> */
    use HasFactory;

    protected $table = 'pmb_referrals';

    protected $fillable = ['kode', 'sekolah', 'pic_nama', 'pic_hp', 'aktif'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }
}
