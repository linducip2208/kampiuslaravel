<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollSlip extends Model
{
    protected $table = 'payroll_slip';

    protected $fillable = ['periode_id', 'user_id', 'gaji_pokok', 'tunjangan', 'potongan', 'total_bersih', 'status'];

    public function periode(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriode::class, 'periode_id');
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
