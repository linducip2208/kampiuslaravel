<div>
    <form wire:submit="scan">
        <input type="text" wire:model="qrToken" placeholder="Tempel / scan token QR">
        <button type="submit">Absen</button>
    </form>
    @if ($message)
        <p>{{ $message }}</p>
    @endif
</div>
