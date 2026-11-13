<?php

namespace App\Services\Ai;

use App\Models\Setting;

/** Resolve driver AI aktif dari Setting DB (ai.driver), fallback config/env. Tanpa hardcode kredensial. */
class AiProvider
{
    public static function driverName(): string
    {
        $name = (string) (Setting::get('ai.driver', '') ?: config('ai.default_driver', 'simulated'));

        return array_key_exists($name, (array) config('ai.drivers', [])) ? $name : 'simulated';
    }

    public static function driver(): AiDriver
    {
        $name = self::driverName();
        $class = config("ai.drivers.{$name}.class", SimulatedAiDriver::class);

        return app($class);
    }

    /** @param list<array{role: string, content: string}> $messages */
    public static function chat(array $messages, array $options = []): string
    {
        return self::driver()->chat($messages, $options);
    }
}
