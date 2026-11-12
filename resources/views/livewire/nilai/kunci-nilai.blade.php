<div class="card">
    <div class="card-body d-flex align-items-center gap-3">
        <div>Total: <strong>{{ $total }}</strong> &middot; Terkunci: <strong>{{ $locked }}</strong></div>
        <div class="ms-auto d-flex gap-2">
            <button wire:click="kunci" class="btn btn-warning" onclick="return confirm('Kunci semua nilai kelas ini?')">Kunci Nilai</button>
            <button wire:click="buka" class="btn btn-outline-secondary" onclick="return confirm('Buka kunci nilai?')">Buka Kunci (Admin)</button>
        </div>
    </div>
</div>
