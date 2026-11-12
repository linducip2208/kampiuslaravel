<?php

use App\Services\Payment\MidtransAdapter;
use App\Services\Payment\SimulatedAdapter;
use App\Services\Payment\TripayAdapter;
use App\Services\Payment\XenditAdapter;

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway (TANPA hardcode)
    |--------------------------------------------------------------------------
    | Tambah gateway baru = buat 1 class adapter + 1 baris di 'drivers'.
    | Kredensial & URL aktif dibaca dari tabel settings (halaman admin
    | Integrasi); nilai di bawah ini hanya default/fallback.
    */
    'default_driver' => env('PAY_DRIVER', 'simulated'),

    'drivers' => [
        'simulated' => ['class' => SimulatedAdapter::class, 'label' => 'Simulasi (dev, tanpa API)'],
        'tripay' => ['class' => TripayAdapter::class, 'label' => 'Tripay (VA / gerai / QRIS)'],
        'midtrans' => ['class' => MidtransAdapter::class, 'label' => 'Midtrans (Core API VA)'],
        'xendit' => ['class' => XenditAdapter::class, 'label' => 'Xendit (Virtual Account)'],
    ],

    'defaults' => [
        'tripay_base_url' => env('TRIPAY_BASE_URL', 'https://tripay.co.id/api-sandbox/'),
        'midtrans_base_url' => env('MIDTRANS_BASE_URL', 'https://api.sandbox.midtrans.com'),
        'xendit_base_url' => env('XENDIT_BASE_URL', 'https://api.xendit.co'),
        'bank' => 'bni',
        'va_prefix' => '8888',
        'expiry_hours' => 24,
    ],
];
