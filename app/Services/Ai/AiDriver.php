<?php

namespace App\Services\Ai;

use App\Models\Setting;

/** Kontrak driver AI. Kredensial/base URL selalu dari Setting DB / env, tidak hardcode. */
interface AiDriver
{
    public function name(): string;

    /** @param list<array{role: string, content: string}> $messages */
    public function chat(array $messages, array $options = []): string;
}
