<?php

namespace App\Services\Ai;

/** Driver deterministik untuk dev/test — tanpa API eksternal. */
class SimulatedAiDriver implements AiDriver
{
    public function name(): string
    {
        return 'simulated';
    }

    public function chat(array $messages, array $options = []): string
    {
        $last = end($messages)['content'] ?? '';

        return 'SIMAK-AI (simulasi): saya menerima "'.mb_substr((string) $last, 0, 160).'". '
            .'Jawaban cerdas aktif setelah admin mengisi provider AI di halaman Integrasi.';
    }
}
