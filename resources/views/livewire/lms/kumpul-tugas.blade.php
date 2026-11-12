<div>
    <h3>{{ $tugas?->judul }}</h3>
    @if($pengumpulan)
        <div class="alert alert-info">Sudah mengumpulkan: {{ $pengumpulan->file_path }} (nilai: {{ $pengumpulan->nilai }})</div>
    @endif
    <form wire:submit="save" enctype="multipart/form-data" class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">File tugas (PDF/DOC/ZIP/JPG, maks 10MB)</label>
                <input type="file" wire:model="file" class="form-control">
                @error('file')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
        </div>
    </form>
</div>
