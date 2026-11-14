<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriode extends Model
{
    protected $table = 'payroll_periode';

    protected $fillable = ['periode', 'status', 'catatan'];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINAL = 'final';

    public function slip(): HasMany
    {
        return $this->hasMany(PayrollSlip::class, 'periode_id');
    }

    public function terkunci(): bool
    {
        return $this->status === self::STATUS_FINAL;
    }
}
