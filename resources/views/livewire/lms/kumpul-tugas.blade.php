<div>
    <h3>{{ $tugas?->judul }}</h3>
    @if ($pengumpulan)
        <p>Sudah mengumpulkan: {{ $pengumpulan->file_path }} (nilai: {{ $pengumpulan->nilai }})</p>
    @endif
    <form wire:submit="save" enctype="multipart/form-data">
        <input type="file" wire:model="file">
        @error('file') <span>{{ $message }}</span> @enderror
        <button type="submit">Upload</button>
    </form>
</div>
