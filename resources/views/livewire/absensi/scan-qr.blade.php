<div>
    @if($message)
        <div class="alert alert-{{ $success ? 'success' : 'warning' }}" role="alert">{{ $message }}</div>
    @endif
    <form wire:submit="scan">
        <div class="mb-3">
            <label class="form-label" for="qrToken">Token QR Pertemuan</label>
            <input id="qrToken" type="text" wire:model="qrToken" class="form-control" placeholder="Tempel / scan token QR">
            @error('qrToken')<div class="text-danger">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary">Absen</button>
    </form>
</div>
