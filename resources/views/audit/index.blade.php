@extends('layouts.tabler')

@section('title', 'Audit Log')
@section('pretitle', 'Admin')
@section('page-title', 'Audit Log')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Aktivitas Sistem</h3>
        <form method="GET" action="{{ route('admin.audit') }}" class="d-flex gap-2">
            <input type="text" name="aksi" value="{{ request('aksi') }}" class="form-control form-control-sm" placeholder="Filter aksi...">
            <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead><tr><th>#</th><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Model</th></tr></thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>{{ $log->created_at?->format('d M Y H:i') }}</td>
                        <td>{{ $log->user->name ?? 'sistem' }}</td>
                        <td><span class="badge bg-secondary">{{ $log->action }}</span></td>
                        <td class="text-secondary small">{{ $log->model ? class_basename($log->model).' #'.$log->model_id : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-secondary">Belum ada aktivitas tercatat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-end">{{ $logs->links() }}</div>
</div>
@endsection
