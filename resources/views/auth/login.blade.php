<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — {{ config('app.name', 'SIMAK Kuliah') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
</head>
<body class="d-flex flex-column">
<div class="page page-center">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card card-lg">
                    <div class="row g-0">
                        <div class="col-md-6 d-none d-md-block bg-primary text-white">
                            <div class="card-body d-flex flex-column justify-content-center h-100 p-5">
                                <h1 class="text-white">SIMAK Kuliah</h1>
                                <p class="text-white-50">Satu kendali untuk setiap fase perjalanan mahasiswa — PMB, KRS, LMS, nilai, skripsi, keuangan, dan surat.</p>
                                <ul class="list-unstyled text-white-50">
                                    <li>✓ Role &amp; scope per pengguna</li>
                                    <li>✓ Audit trail tercatat</li>
                                    <li>✓ API v1 untuk mobile</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card-body p-5">
                                <h2 class="h2 text-center mb-4">Masuk ke akun</h2>
                                @if($errors->any())
                                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                                @endif
                                <form method="POST" action="{{ route('login.attempt') }}" autocomplete="off">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label" for="email">Email</label>
                                        <input id="email" type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="password">Password</label>
                                        <input id="password" type="password" name="password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-check"><input type="checkbox" name="remember" class="form-check-input"><span class="form-check-label">Ingat saya</span></label>
                                    </div>
                                    <div class="form-footer">
                                        <button type="submit" class="btn btn-primary w-100">Masuk</button>
                                    </div>
                                </form>
                                <div class="text-center text-secondary mt-3">
                                    Akun demo: superadmin / dosen / mahasiswa@kampus.ac.id — hubungi admin untuk kata sandi.
                                </div>
                                <div class="text-center text-secondary mt-2"><a href="{{ route('home') }}">Kembali ke beranda</a></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
