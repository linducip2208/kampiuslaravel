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
    <div class="container container-tight py-4">
        <div class="text-center mb-4"><h1>SIMAK Kuliah</h1></div>
        <div class="card card-md">
            <div class="card-body">
                <h2 class="h2 text-center mb-4">Masuk ke akun</h2>
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('login.attempt') }}" autocomplete="off">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-check"><input type="checkbox" name="remember" class="form-check-input"><span class="form-check-label">Ingat saya</span></label>
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary w-100">Masuk</button>
                    </div>
                </form>
                <div class="text-center text-secondary mt-3">
                    Demo: superadmin@kampus.ac.id / dosen@kampus.ac.id / mahasiswa@kampus.ac.id (password: <code>password</code>)
                </div>
            </div>
        </div>
        <div class="text-center text-secondary mt-3"><a href="{{ route('home') }}">Kembali ke beranda</a></div>
    </div>
</div>
</body>
</html>
