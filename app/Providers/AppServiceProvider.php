<?php

namespace App\Providers;

use App\Models\Pengumuman;
use App\Models\TahunAkademik;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        // Data shell aplikasi: notifikasi terbaru + periode akademik aktif.
        // Dijaga Schema::hasTable agar aman saat migrate/seed parsial.
        View::composer('layouts.tabler', function ($view): void {
            $notifikasi = collect();
            $periode = '—';
            $belumDibaca = 0;
            try {
                if (Schema::hasTable('pengumuman')) {
                    $notifikasi = Pengumuman::whereNotNull('published_at')
                        ->latest('published_at')->take(5)->get();
                    $belumDibaca = $notifikasi->count();
                }
                if (Schema::hasTable('tahun_akademik')) {
                    $ta = TahunAkademik::where('aktif', true)->first();
                    if ($ta) {
                        $periode = $ta->tahun.' · '.ucfirst((string) $ta->semester);
                    }
                }
            } catch (Throwable) {
                // abaikan: shell tetap render tanpa data dinamis
            }
            $view->with([
                'notifikasiTerbaru' => $notifikasi,
                'notifikasiCount' => $belumDibaca,
                'periodeAktif' => $periode,
            ]);
        });
    }
}
