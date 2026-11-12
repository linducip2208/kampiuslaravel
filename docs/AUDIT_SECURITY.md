# Audit Keamanan — SIMAK Kuliah (Laravel 13, Spatie Permission, Sanctum)

Tanggal: 2026-11-12 · Sifat: READ-ONLY, tanpa perbaikan kode.
Cakupan: `bootstrap/app.php`, seluruh controller (`app/Http/Controllers/**`, `Api/V1/**`),
`routes/web.php`, `routes/api.php`, Livewire (`app/Livewire/**`), model fillable,
upload file, callback gateway, `NotifikasiService`, `ComfyUIService`, Sanctum/throttle,
info exposure, audit trail.

Konvensi severity: **Critical** = eksploitasi langsung (ambil alih data/uang/akses).
**High** = IDOR/SSRF/privesk praktis. **Medium** = kebocoran info/validasi.
**Low** = hardening.

---

## Ringkasan eksekutif

Masalah struktural terbesar: **otorisasi hanya mengandalkan "menu disembunyikan"**.
Hampir semua route web sensitif hanya memakai middleware `auth` tanpa
role/permission/policy, dan hampir semua aksi Livewire tanpa `authorize()`.
Akibatnya mahasiswa terautentikasi dapat membaca KHS/transkrip/KRS/kwitansi/surat
milik orang lain, memverifikasi pembayaran, menerbitkan nomor surat, mengubah nilai,
dan mengubah kredensial integrasi. Temuan Critical #2 (callback simulasi tanpa
signature) memungkinkan pelunasan tagihan palsu bila driver `simulated` aktif di
produksi (yang saat ini adalah default).

---

## Temuan

### C-01 — IDOR + missing authorization massal di controller web sensitif (Critical)

| | |
|---|---|
| Lokasi | `app/Http/Controllers/KhsController.php:10-12`, `TranskripController.php:10-12`, `KrsController.php:15-19`, `KwitansiController.php:10-14`, `SuratController.php:11-21`, `PddiktiController.php:14-40`, `DashboardController.php:15-43`, `routes/web.php:71-144` |
| Bukti | `KhsController::show(int $mahasiswaId)` langsung `Nilai::where('mahasiswa_id', $mahasiswaId)->get()` tanpa cek pemilik/role. Sama untuk `TranskripController::cetak`, `KrsController::cetak` (`$krs->load('details')` tanpa cek), `KwitansiController::cetak` (hanya cek `status==='verified'`, bukan kepemilikan), `SuratController::cetak` (hanya cek `nomor_surat` ada). `PddiktiController::index/export/push` tanpa role — siapa pun bisa `exportCsv('mahasiswa')` (seluruh NIM/nama) dan `push(act, record)` dengan `act` arbitrer. Semua route di `routes/web.php:71` hanya dalam `Route::middleware('auth')` tanpa `role:`/`can:`. |
| Dampak | Mahasiswa A membaca KHS/IPK/transkrip/KRS/keuangan/surat mahasiswa B dengan menebak ID berurutan (`/khs/2`, `/transkrip/2/cetak`, `/krs/5/cetak`, `/kwitansi/9/cetak`, `/surat/9/cetak`). Juga unduh CSV PDDikti penuh. |
| Perbaikan | Tambah policy/gate per model (`viewAny/view`), mis. `KhsPolicy`: pemilik (`mahasiswa.user_id === auth()->id()`), dosen PA yang bersangkutan, atau role `akademik/pimpinan`. Terapkan di controller (`$this->authorize('view', $mahasiswa)`) + middleware route `->middleware('role:akademik|...')`. Untuk PDDikti: `role:admin|akademik` + audit tiap export/push. Jangan pernah izinkan IDOR via auto-increment — pertimbangkan scope `where mahasiswa milik user` sebagai default. |

### C-02 — Callback payment tanpa signature pada driver default + CSRF-exempt (Critical)

| | |
|---|---|
| Lokasi | `app/Services/Payment/SimulatedAdapter.php:23-26`, `config/payment.php:18` (`default_driver => env('PAY_DRIVER','simulated')`), `.env` (tanpa `PAY_DRIVER`), `bootstrap/app.php:18` (`validateCsrfTokens except payment/callback`), `routes/web.php:69`, `app/Http/Controllers/BayarController.php:52-68`, `app/Services/Payment/PaymentService.php:81-100` |
| Bukti | `SimulatedAdapter::verifyCallback` = `return ($payload['status'] ?? '') === 'paid' ? ($payload['reference'] ?? null) : null;` — tanpa HMAC/signature/shared-secret apa pun. Route `POST /payment/callback` bebas CSRF + tanpa auth + tanpa throttle. `BayarController::callback` meneruskan ke `verifyCallback` lalu `settleByReference($reference)` yang mengeset `verified`. |
| Dampak | Siapa pun (tanpa login) dapat melunasi tagihan sendiri: buat VA (`POST /keuangan/bayar/{tagihan}/va` sebagai pemilik — sah), lalu `POST /payment/callback {status:paid, reference:SIM-...}` → tagihan jadi `lunas`. Tidak butuh akses gateway. |
| Perbaikan | (1) Jangan pernah aktifkan `simulated` di non-local: `abort_unless(app()->isLocal(), 404)` di callback bila driver simulated, atau hapus driver dari `availableDrivers()` saat `APP_ENV=production`. (2) Tambah shared-secret untuk simulasi dev juga. (3) Beri `throttle:30,1` + log IP/body callback + idempotency key. (4) `settleByReference` harus `lockForUpdate` + cek `verified_at` + verifikasi nominal (`gross_amount == tagihan.nominal`) sebelum `verified`. |

### C-03 — Semua aksi Livewire sensitif tanpa otorisasi (Critical)

| | |
|---|---|
| Lokasi | `app/Livewire/Keuangan/VerifikasiBayar.php:17-45`, `Keuangan/TagihanMassal.php:26-54`, `Surat/NomorOtomatis.php:17-47`, `Skripsi/SetPembimbing.php:23-49`, `Sidang/NilaiSidang.php:26-79`, `Nilai/InputNilai.php:44-73`, `Nilai/KunciNilai.php:17-27`, `Krs/PersetujuanPa.php:29-48`, `Pmb/Verifikasi.php:38-105`, `Admin/Integrasi.php:48-141`, `Notifikasi/Broadcast.php:18-30`, `Media/Generator.php:26-45`, `Lms/TugasCrud.php:37-69`, `Lms/MateriCrud.php:40-66`, `Bimbingan/Log.php:52-70` |
| Bukti | Tidak ada satu pun `authorize()`/`Gate`/`hasRole` di file-file di atas (grep `authorize|Gate::|can\(|hasRole` hanya kena `BayarController`). Contoh: `VerifikasiBayar::verify($id)` langsung `KeuanganService::verifyPembayaran($id, Auth::id(), true)` — ID pembayaran arbitrer. `TagihanMassal::generate()` langsung `generateMassal(...)`. `NomorOtomatis::proses/selesaikan/tolak` tanpa cek role TU. `Integrasi::save/test` (baca/tulis semua kredensial gateway/FCM/PDDikti/ComfyUI + SSRF test) tanpa role. Livewire diakses via endpoint `/livewire/update` — menyembunyikan menu tidak mencegah pemanggilan langsung. |
| Dampak | Mahasiswa login dapat: verifikasi/reject pembayaran siapa pun, generate tagihan massal, terbitkan nomor surat, ubah status PMB + buat akun (`daftarUlang`), set pembimbing + nilai sidang, kunci/buka nilai, broadcast notifikasi ke semua role, baca/timpa kredensial payment/FCM/Feeder/ComfyUI. = eskalasi penuh ke admin/keuangan. |
| Perbaikan | Tambah `#[Authorize]` / `$this->authorize(...)` atau guard di `mount()`+ tiap aksi, mis.: `abort_unless(auth()->user()->hasRole(['keuangan']), 403)` di `VerifikasiBayar`; `hasRole('admin|akademik')` di `NomorOtomatis/SetPembimbing/NilaiSidang/KunciNilai/InputNilai/PersetujuanPa`; `hasRole('superadmin')` di `Integrasi`; `hasRole('admin|pimpinan')` di `Broadcast`. Tambah test: user mahasiswa memanggil aksi → 403. |

### C-04 — Upload file ke disk publik tanpa batasan tipe → RCE potensial (Critical)

| | |
|---|---|
| Lokasi | `app/Livewire/Lms/KumpulTugas.php:28-37`, `app/Livewire/Lms/MateriCrud.php:27-47`, `config/filesystems.php:41-48` (disk `public` → `storage/app/public`), `docs/COMFYUI.md:42` (`storage:link`), `app/Http/Controllers/Api/V1/TugasController.php:32-37`, `BimbinganController.php:42-46` |
| Bukti | `KumpulTugas::save` validasi hanya `required\|file\|max:10240` — **tanpa `mimes`** — lalu `$this->file->store('tugas_kumpul','public')` (nama acak tapi ekstensi asli dipertahankan Laravel). `MateriCrud` sama (`nullable\|file\|max:10240`). Dengan `php artisan storage:link`, file tersedia di `/storage/tugas_kumpul/xxx.php`. API memang memakai `mimes:pdf,doc,docx,jpg,jpeg,png,zip` tapi itu hanya cek ekstensi/mime client — `zip` boleh berisi apa pun dan tetap publik. |
| Dampak | Upload webshell `.php`/`.phtml`/`.phar` → eksekusi kode di server bila server mengeksekusi PHP di `public/storage` (Apache+mod_php / misconfig Nginx umum). Minimal: hosting konten berbahaya + deface + phishing dari domain kampus. |
| Perbaikan | (1) Ketatkan: `mimes:pdf,doc,docx,jpg,jpeg,png` + `max` + `dimensions` untuk gambar; **tolak `php,phtml,phar,svg,html,zip`** untuk tugas. (2) Simpan ke disk `local` (non-publik) + serve via controller dengan `authorize` + `response()->file()`. (3) Jika harus publik: rename paksa ekstensi aman + `storeAs` whitelist + blokir eksekusi (`.htaccess` `php_flag engine off` / Nginx `location ^~ /storage/ { add_header X-Content-Type-Options nosniff; }` + tidak pass ke php-fpm). (4) Samakan aturan Livewire = API. |

### H-01 — Spoofing identitas via parameter (mahasiswaId/judul_id) + fallback data terbaru (High)

| | |
|---|---|
| Lokasi | `routes/web.php:88-99` (`?kelas=?pertemuan=?tugas=` + fallback `Mahasiswa::latest('id')`), `app/Livewire/Lms/KumpulTugas.php:20-24,39-42`, `app/Livewire/Absensi/ScanQr.php:19-38`, `app/Http/Controllers/Api/V1/BimbinganController.php:56-63`, `app/Livewire/Sidang/Pendaftaran.php:37-52`, `app/Http/Controllers/Api/V1/TugasController.php:47-57` |
| Bukti | `KumpulTugas::mount(tugasId, mahasiswaId)` dan `ScanQr::mount(mahasiswaId)` percaya `mahasiswaId` dari query/route (`web.php:93-99` bahkan fallback ke `Mahasiswa::latest('id')` bila user bukan mahasiswa — dosen/admin bisa "kumpul sebagai mahasiswa terakhir"). `BimbinganController::store` dan `Sidang\Pendaftaran::save` hanya `exists:skripsi_judul,id` tanpa cek `judul.mahasiswa_id === milik user` → bisa menempelkan log/catatan ke judul orang lain (pencemaran data + kuota). `TugasController::kumpul` bila user bukan mahasiswa → `$mahasiswaId = null` lalu `updateOrCreate(['tugas_id','mahasiswa_id'=>null])` — satu baris `null` dipakai bersama semua non-mahasiswa (tabrakan/overwrite). |
| Perbaikan | Derive `mahasiswaId` server-side: `Mahasiswa::where('user_id', auth()->id())->firstOrFail()`; tolak bila user bukan mahasiswa (403), hapus fallback `latest('id')`. Untuk `judul_id`: `SkripsiJudul::where('id',$id)->where('mahasiswa_id',$mhs->id)->firstOrFail()` (atau cek pembimbing untuk aksi dosen). Tambah composite unique `(tugas_id, mahasiswa_id)` + NOT NULL. |

### H-02 — SSRF via URL dari setting/input yang bisa diubah user (High)

| | |
|---|---|
| Lokasi | `app/Livewire/Admin/Integrasi.php:59-102` (`test()`), `app/Services/ComfyUIService.php:28-69`, `app/Services/PddiktiService.php:21-50`, `app/Services/NotifikasiService.php:59-85`, `config/comfyui.php:12`, `config/payment.php:28-30` |
| Bukti | `Integrasi::test('comfyui'|'tripay'|'pddikti')` memakai `$this->comfyui_url / pay_base_url / pddikti_base_url` — properti yang diisi user dan **belum tersimpan** — lalu `Http::get/post($url...)` langsung. Tidak ada allowlist skema/host, tidak ada blok IP privat/metadata (`169.254.169.254`, `127.0.0.1`, `10/8`, `192.168/16`). `PddiktiService::__construct` memakai `Setting::get('pddikti.base_url')` (nilai DB yang bisa ditulis via `Integrasi::save` oleh siapa pun — lihat C-03) untuk semua `Http::post($this->base)`. `ComfyUIService` sama (`config('comfyui.url')` yang di-sync dari Setting). |
| Dampak | Pemindaian port internal, baca metadata cloud (kredensial IAM), pivot ke Feeder/internal bila ada. Diperparah C-03: **semua user auth** bisa memanggil `test()`, bukan hanya admin. |
| Perbaikan | (1) Kunci `Integrasi` ke `role:superadmin` (lihat C-03) — mengurangi tapi tidak menyembuhkan. (2) Validasi URL: `https` saja di produksi, hostname allowlist/deny-list IP privat via `filter_var` + `gethostbyname` + tolak `10/8,172.16/12,192.168/16,127/8,169.254/16,::1`. (3) Timeout + `maxRedirects(0)` + nonaktifkan follow ke host beda. (4) Untuk `test()`: gunakan nilai **tersimpan** (Setting), bukan input mentah; rate-limit. |

### H-03 — LFI/path arbitrary read via `fcm.service_account_path` (High)

| | |
|---|---|
| Lokasi | `app/Services/NotifikasiService.php:43-53,65-67`, `app/Livewire/Admin/Integrasi.php:104-115,119-141` |
| Bukti | `pushFcm()` → `is_file($serviceAccount)` → `file_get_contents($serviceAccountPath)` dengan path dari `Setting::get('fcm.service_account_path')`. Nilai itu ditulis lewat `Integrasi::save()` (validasi hanya `required/in/url` untuk 3 field — **path FCM tanpa validasi sama sekali**, `Integrasi.php:130-134`) oleh siapa pun (C-03). `tesFcm()` juga `@file_get_contents($this->fcm_service_account_path)` mentah. |
| Dampak | Baca file lokal arbitrer yang bisa dibaca php (`/etc/passwd`, `.env` bila path traversal, key lain) — isi masuk ke log/error atau diuji validitasnya; dikombinasi C-03 menjadi LFI terautentikasi. |
| Perbaikan | Jangan simpan path bebas — simpan **upload JSON via disk privat** (`Storage::disk('local')`) atau referensi `storage/app/fcm/*.json` yang divalidasi basename (`basename()` + allowlist dir + `realpath` harus di bawah dir). Validasi JSON berisi `client_email+private_key` saat save, jangan saat kirim. Kunci ke superadmin. |

### H-04 — Login web tanpa throttle; token Sanctum abadi + ability tak ditegakkan (High)

| | |
|---|---|
| Lokasi | `routes/web.php:52-53`, `app/Http/Controllers/SessionAuthController.php:17-31`, `routes/api.php:16-17` (throttle hanya login API), `config/sanctum.php:52` (`expiration => null`), `app/Http/Controllers/Api/V1/AuthController.php:31,45-48` |
| Bukti | `POST /login` (session) tanpa `throttle` — brute-force/credential-stuffing tak dibatasi (API dibatasi `throttle:10,1`, web tidak). Token mobile dibuat `createToken('mobile',['mobile'])` tapi tidak ada `tokenCan('mobile')`/ability check di route mana pun — ability mati. `expiration=null` → token berlaku selamanya; `logout` hanya `currentAccessToken()->delete()` (sesi lain tetap hidup, tidak ada revoke-all / daftar sesi). |
| Perbaikan | Tambah `->middleware('throttle:5,1')` (atau `RateLimiter::for('login')` per email+IP) di `POST /login` + lockout + captcha setelah gagal. Set `sanctum.expiration` (mis. 60*24*30) + refresh flow; tegakkan `->middleware('ability:mobile')` atau hapus ability agar tidak menyesatkan; tambah `POST /v1/logout-all` (`$user->tokens()->delete()`) + audit login. |

### H-05 — PDDikti export/push tanpa role + `act` arbitrer (High)

| | |
|---|---|
| Lokasi | `app/Http/Controllers/PddiktiController.php:19-33`, `app/Services/PddiktiService.php:35-50`, `routes/web.php:138-140` |
| Bukti | `export($jenis)` hanya whitelist jenis — tapi tanpa role, mengembalikan CSV NIM/nama/prodi/angkatan/status + nilai + kelulusan seluruh kampus via `Storage::download($path)` (file di `storage/app/exports/...` dengan nama timestamp yang bisa ditebak pola). `push(Request)` validasi hanya `act|string|max:60 + record|array` lalu diteruskan mentah ke Feeder — `act` bebas (`GetToken` hingga `Delete*`) dengan kredensial server. |
| Perbaikan | `role:admin|akademik` + `AuditLog` tiap export/push (siapa, kapan, jenis, jumlah baris). Whitelist `act` (`Rule::in([...])`), validasi `record` per-act (FormRequest), batasi rate, hapus file export setelah download (`deleteFileAfterSend`) atau simpan di disk privat. |

### H-06 — Xendit `inactive` dianggap `verified`; nominal tak dicek saat settle (High)

| | |
|---|---|
| Lokasi | `app/Services/Payment/XenditAdapter.php:50-72`, `app/Services/Payment/PaymentService.php:81-122` |
| Bukti | `checkStatus()`: `'paid','completed','inactive' => 'verified'` — padahal `inactive` di Xendit = VA kedaluwarsa/dinonaktifkan, bukan lunas. `settleByReference()` tidak membandingkan nominal callback/gateway vs `tagihan.nominal`, tidak lock baris (berbeda dengan `KeuanganService::verifyPembayaran` yang benar memakai `lockForUpdate`). Callback Xendit hanya cek `callback_token` statis — token bocor di log/Setting = replay bebas. |
| Perbaikan | Petakan `inactive/expired => failed`; cek silang `expected_amount/amount == tagihan.nominal` sebelum `verified`; bungkus settle dalam transaksi + `lockForUpdate`; tolak callback duplikat bila sudah `verified` (sudah ada, pertahankan) + catat `AuditLog payment.callback`. Rotasi `callback_token` + simpan hashed. |

### M-01 — Info disclosure publik & enumerasi PII (Medium)

| | |
|---|---|
| Lokasi | `.env:4` (`APP_DEBUG=true`), `.env.example:4`, `resources/views/auth/login.blade.php:37` (kredensial demo), `routes/web.php:30-50` + `resources/views/welcome.blade.php:43-51,78` (statistik publik), `app/Http/Controllers/PmbController.php:47-62` + `resources/views/pmb/cek.blade.php:27-28`, `app/Http/Controllers/SuratController.php:23-37` + `cetak/surat-verify.blade.php`, `resources/views/cetak/{surat,kwitansi}.blade.php:14` |
| Bukti | `APP_DEBUG=true` di env aktif → stack trace Laravel (query SQL, path, potongan config) ke pengunjung saat error. Halaman `/` membeberkan `audit_logs count`, `piutang` (total nominal−terbayar), `krs_pending`, `krs_ok` — data operasional internal. `/pmb/cek?no_daftar=PMB-2026-000001` dapat di-bruteforce (format sekuensial, tanpa captcha/rate-limit) → panen nama/email/HP/sekolah pendaftar. `/surat/verifikasi/{nomor}` publik dengan nomor pola `JENIS/UNIV/TAHUN/000N` yang mudah ditebak → mengembalikan status + view berisi NIM/nama (lihat `cetak/surat.blade.php:14`). Login memajang `superadmin@kampus.ac.id / dosen@... / mahasiswa@... password: password` — seeding kredensial lemah di produksi = undangan brute-force. |
| Perbaikan | `APP_DEBUG=false` di prod + `shouldRenderJsonWhen` sudah ada, tambah handler 404/500 generik. Hapus kredensial demo dari view (pindah ke seeder lokal saja). Kurangi statistik publik (tampilkan angka umum saja) atau cache+rate-limit. Tambah `throttle` + captcha di PMB cek/verifikasi surat; kembalikan respons generik "tidak ditemukan" dengan timing seragam; pertimbangkan token verifikasi acak (signed URL) selain nomor surat. |

### M-02 — Validasi & mass-assignment longgar (Medium)

| | |
|---|---|
| Lokasi | `app/Models/Pembayaran.php:15` (`status,verified_by` fillable), `app/Models/Nilai.php:12-15` (`is_locked,bobot,huruf,na` fillable), `app/Livewire/Krs/Pengajuan.php:117-122` (`ipk` dari client), `app/Livewire/Surat/Pengajuan.php:21-24` (`keperluan` tanpa max), `app/Http/Controllers/Api/V1/TugasController.php:22` (list 20 tugas global), `app/Http/Controllers/Api/V1/JadwalController.php:28-30` (fallback semua kelas), `app/Livewire/Pmb/DaftarPublik.php:37-46` (tanpa captcha) |
| Bukti | Fillable sensitif tidak berbahaya selama create/update hanya di service — tapi pola ini rapuh (satu `Model::create($request->all())` di masa depan = privesk status). `Krs\Pengajuan` memvalidasi `ipk` dari client (`min:0,max:4`) lalu dipakai menentukan `sksMaks` — user bisa menaikkan IPK untuk meloloskan SKS berlebih (server harus hitung IPK dari `Nilai`, bukan dari input). `TugasController::index` dan `JadwalController::hariIni` mengembalikan data global bila relasi tak ada (over-collection). PMB publik tanpa rate-limit/captcha → spam + enumerasi email (`unique:pmb_pendaftars,email` memberi oracle). |
| Perbaikan | Kurangi fillable ke kolom non-otoritatif (`$guarded=['id','status','verified_*','is_locked',...]` + FormRequest whitelist). Hitung `ipk` server-side. Tambah `max:500` di `keperluan`, scope tugas/jadwal ke kelas/KRS milik user, tambah `throttle:5,1` + Turnstile/captcha di PMB. |

### M-03 — Jejak audit tidak lengkap (Medium)

| | |
|---|---|
| Lokasi | `app/Models/AuditLog.php:14`, grep `AuditLog::create` (hanya: Surat Pengajuan/NomorOtomatis, Skripsi, Keuangan TagihanMassal/VerifikasiBayar, Sidang, Bimbingan Log) |
| Bukti | Tidak ada audit untuk: login/logout (web+API), KRS ajukan/setujui/tolak, input/kunci nilai, PDDikti export/push, `PaymentService::settleByReference`/callback, perubahan `Setting`/Integrasi, generate media, verifikasi PMB/daftarUlang, baca KHS/transkrip. `diff_json` hanya diisi TagihanMassal; lainnya null. `AuditLog::$timestamps=false` + `created_at` manual — mudah lupa. |
| Perbaikan | Tambah observer/listener terpusat (`Login/Logout`, `KrsObserver`, `NilaiObserver`, `PaymentSettled` event) + wajibkan `diff_json` (before/after) + IP + user-agent. Pertimbangkan tabel append-only (tanpa update/delete oleh app). |

### M-04 — Callback tanpa throttle + logika `syncStatus` bisa menurunkan status (Medium)

| | |
|---|---|
| Lokasi | `routes/web.php:69`, `app/Http/Controllers/BayarController.php:52-78`, `app/Services/Payment/PaymentService.php:102-122` |
| Bukti | Callback & `status` tanpa `throttle` → penyerang bisa membanjiri gateway check (`checkStatus` → HTTP ke Midtrans/Tripay/Xendit per request) = SSRF amplification + biaya. `syncStatus` mengubah `pending→rejected` bila gateway bilang `failed`, tapi tidak menangani kasus VA sudah `verified` manual lalu gateway bilang lain (saat ini aman karena early-return `verified`, pertahankan + tambah komentar). Error gateway (`422` dengan message mentah `$e->getMessage()`) membocorkan detail kredensial/URL ke user (`BayarController:41-43`, `TagihanController:66-68` API). |
| Perbaikan | `throttle:30,1` di callback + `throttle:10,1` di status/VA; cache hasil `checkStatus` singkat; ganti pesan error ke generik ("Gagal membuat VA, hubungi keuangan") + log detail server-side. |

### L-01 — Hardening sesi, cookie, dan misc (Low)

| | |
|---|---|
| Lokasi | `.env:29-33` (`SESSION_DRIVER=database`, `SESSION_ENCRYPT=false`, tanpa `SESSION_SECURE_COOKIE/SESSION_HTTP_ONLY/SESSION_SAME_SITE`), `config/sanctum.php:21-26`, `app/Livewire/Pmb/Verifikasi.php:89-101` |
| Bukti | `SESSION_ENCRYPT=false` (default Laravel, payload sesi di DB plaintext — masih ok karena DB privat, tapi naikkan bila sesi menyimpan sensitif). Tidak ada penegasan `Secure/HttpOnly/SameSite=lax` via env. `daftarUlang` memakai `firstOrCreate(['email'=>...])` → bila email pendaftar sama dengan akun dosen/admin eksisting, pendaftar di-link ke akun istimewa itu (`update user_id`) — pengambilalihan asosiasi. Password acak 12 char dibuat tapi tidak pernah disampaikan aman ke pendaftar (akun tak bisa dipakai / justru harus reset manual). |
| Perbaikan | Set `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax` di prod; aktifkan `SESSION_ENCRYPT=true` bila memungkinkan. `daftarUlang`: tolak bila email sudah dipakai (`unique:users,email` explisit, jangan `firstOrCreate`), kirim kredensial via channel resmi + wajibkan reset saat login pertama + audit. |

---

## Cakupan yang dicek dan dinyatakan OK / catatan

- CSRF: `bootstrap/app.php:15-19` — pengecualian hanya `payment/callback`, wajar karena server-to-server; keaslian wajib via signature adapter (Midtrans/Tripay/Xendit memakai `hash`/`hash_hmac`/`hash_equals` — benar; kecuali Simulated, lihat C-02).
- Session fixation: `SessionAuthController.php:25` (`regenerate` saat login) + `logout`: `invalidate+regenerateToken` — **OK**.
- Password hashing: `User::casts()['password']='hashed'` (`User.php:41-47`) + `Hash::check` di API login — **OK**.
- Kemampuan peran: `User` memakai `HasRoles` (Spatie 6) — infrastruktur ada, hanya belum dipakai di route/Livewire (lihat C-01/C-03).
- Absensi QR: `AbsensiService.php:18` memakai `hash_equals` + cek expiry/window — **OK** di level service; yang bocor adalah lapisan pemanggil (H-01), bukan kripto QR.
- `NotifikasiService::broadcast` memakai `chunk(100)` — OK dari sisi performa; masalahnya otorisasi pemanggil (C-03), bukan query.
- `ComfyUIService::fetchOutput` memvalidasi ekstensi output (`isImage`, baris 180-183) dan menyimpan dengan UUID (`ComfyUIService.php:88-92`) — pola nama aman; risiko utama tetap SSRF base URL (H-02) + blocking `sleep()` di request (DoS worker — pindahkan ke queue, `GenerateMediaJob` sudah queue, pertahankan + batasi `timeout/poll_interval`).

## Rekomendasi prioritas (tanpa mengubah kode pada audit ini)

1. Kunci semua route sensitif + Livewire ke role (C-01, C-03) — dampak terbesar.
2. Matikan/skop `simulated` dan perkuat settle payment (C-02, H-06).
3. Pindahkan upload ke disk privat + ketat mimes (C-04) + derive identitas server-side (H-01).
4. Kunci `Integrasi`, allowlist URL, dan simpan service-account sebagai file terunggah (H-02, H-03), throttle login web + expiry token (H-04).
5. Bersihkan info publik, captcha PMB, lengkapi audit trail (M-01–M-03).
