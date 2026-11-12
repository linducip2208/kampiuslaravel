<div>
    @if($message)
        <div class="alert alert-success">{{ $message }}</div>
    @endif

    <form wire:submit="queue" class="card mb-3">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Prompt</label>
                <textarea wire:model="prompt" class="form-control" rows="3" placeholder="Mahasiswa Indonesia wisuda di kampus, fotorealistik, siang hari"></textarea>
                @error('prompt')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Negative prompt (opsional)</label>
                <input type="text" wire:model="negative" class="form-control" placeholder="blurry, watermark">
            </div>
            <div class="row">
                <div class="col-4 mb-3"><label class="form-label">Steps</label><input type="number" wire:model="steps" class="form-control"></div>
                <div class="col-4 mb-3"><label class="form-label">Lebar</label><input type="number" wire:model="width" class="form-control" step="64"></div>
                <div class="col-4 mb-3"><label class="form-label">Tinggi</label><input type="number" wire:model="height" class="form-control" step="64"></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Jadikan cover pengumuman (opsional)</label>
                <select wire:model="pengumuman_id" class="form-select">
                    <option value="">— Hanya simpan file —</option>
                    @foreach($pengumuman as $p)
                        <option value="{{ $p->id }}">#{{ $p->id }} — {{ $p->judul }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Generate via ComfyUI (WAN 2.1)</button>
            <span class="text-secondary ms-2">Server: {{ config('comfyui.url') }} &middot; Model: {{ config('comfyui.model') }}</span>
        </div>
    </form>

    <h3 class="mb-2">Hasil Terakhir</h3>
    <div class="row row-cards">
        @forelse($files as $f)
            <div class="col-6 col-md-3">
                <div class="card"><img src="{{ asset('storage/' . $f) }}" class="card-img-top" alt="AI generated"></div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info">Belum ada gambar. Pastikan ComfyUI jalan lalu generate pertama.</div></div>
        @endforelse
    </div>
</div>
