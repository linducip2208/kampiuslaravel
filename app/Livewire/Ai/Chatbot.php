<?php

namespace App\Livewire\Ai;

use App\Livewire\Concerns\RequiresRoles;
use App\Services\Ai\ChatbotService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Chatbot extends Component
{
    use RequiresRoles;

    public array $allowedRoles = [];

    /** @var list<array{dari: string, teks: string}> */
    public array $riwayat = [];

    public string $pesan = '';

    public function kirim(ChatbotService $bot): void
    {
        $this->validate(['pesan' => 'required|string|min:2|max:2000']);

        $jawaban = $bot->ask(Auth::id(), $this->pesan);

        $this->riwayat[] = ['dari' => 'kamu', 'teks' => $this->pesan];
        $this->riwayat[] = ['dari' => 'ai', 'teks' => $jawaban];
        $this->riwayat = array_slice($this->riwayat, -20);
        $this->reset('pesan');
    }

    public function render()
    {
        return view('livewire.ai.chatbot');
    }
}
