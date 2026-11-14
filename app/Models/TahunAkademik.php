<?php

namespace App\Models;

use Database\Factories\TahunAkademikFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class TahunAkademik extends Model
{
    /** @use HasFactory<TahunAkademikFactory> */
    use HasFactory;

    protected $table = 'tahun_akademik';

    protected $fillable = ['tahun', 'semester', 'aktif'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Kelas, $this>
     */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    /**
     * M-021: aktifkan tepat 1 periode (transaksi + kunci baris).
     * Invarian "1 aktif" tidak bisa partial-unique lintas SQLite/MySQL,
     * jadi nonaktifkan semua dulu di dalam transaksi yang sama.
     */
    public static function aktifkan(int $id): self
    {
        return DB::transaction(function () use ($id): self {
            $target = self::whereKey($id)->lockForUpdate()->firstOrFail();

            self::where('aktif', true)->whereKeyNot($id)->update(['aktif' => false]);
            $target->update(['aktif' => true]);

            return $target->fresh();
        });
    }

    /**
     * M-021: rollover ke periode berikut (ganjil→genap tahun sama;
     * genap→ganjil tahun+1) lalu aktifkan. Idempoten: periode tujuan
     * yang sudah ada dipakai ulang, bukan diduplikasi.
     */
    public static function rollover(?string $tahun = null, ?string $semester = null): self
    {
        return DB::transaction(function () use ($tahun, $semester): self {
            if ($tahun === null || $semester === null) {
                $aktif = self::where('aktif', true)->lockForUpdate()->first();

                if ($aktif) {
                    [$tahun, $semester] = self::berikutnya($aktif->tahun, $aktif->semester);
                } else {
                    $tahun ??= (string) now()->year;
                    $semester ??= 'ganjil';
                }
            }

            $periode = self::firstOrCreate(
                ['tahun' => $tahun, 'semester' => $semester],
                ['aktif' => false]
            );

            return self::aktifkan($periode->id);
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function berikutnya(string $tahun, string $semester): array
    {
        return strtolower($semester) === 'ganjil'
            ? [$tahun, 'genap']
            : [(string) ((int) $tahun + 1), 'ganjil'];
    }

    /** @return HasMany<Kelas, $this> */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }
}
