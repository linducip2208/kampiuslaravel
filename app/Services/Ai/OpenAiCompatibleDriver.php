<?php

namespace App\Services\Ai;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

/** Driver OpenAI-compatible (chat/completions). Konfigurasi dari Setting ai.*. */
class OpenAiCompatibleDriver implements AiDriver
{
    public function name(): string
    {
        return 'openai_compatible';
    }

    public function chat(array $messages, array $options = []): string
    {
        $baseUrl = rtrim((string) (Setting::get('ai.base_url', '') ?: ''), '/');
        $apiKey = (string) (Setting::get('ai.api_key', '') ?: '');
        $model = (string) (Setting::get('ai.model', '') ?: config('ai.defaults.model'));

        if ($baseUrl === '' || $apiKey === '') {
            return app(SimulatedAiDriver::class)->chat($messages, $options);
        }

        $res = Http::withToken($apiKey)
            ->timeout((int) config('ai.defaults.timeout', 60))
            ->post($baseUrl.'/chat/completions', [
                'model' => $options['model'] ?? $model,
                'messages' => $messages,
                'max_tokens' => $options['max_tokens'] ?? (int) config('ai.defaults.max_tokens', 800),
                'temperature' => $options['temperature'] ?? 0.3,
            ]);

        if (! $res->successful()) {
            return app(SimulatedAiDriver::class)->chat($messages, $options);
        }

        $text = $res->json('choices.0.message.content');

        return is_string($text) && $text !== '' ? $text : app(SimulatedAiDriver::class)->chat($messages, $options);
    }
}
