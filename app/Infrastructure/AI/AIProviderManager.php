<?php

namespace App\Infrastructure\AI;

use App\Domain\Chat\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Log;

/**
 * Resolves the active AI provider from config, falling back to the keyless
 * MockProvider whenever the configured real provider is unavailable.
 */
class AIProviderManager
{
    private ?AIProviderInterface $resolved = null;

    public function provider(): AIProviderInterface
    {
        if ($this->resolved instanceof AIProviderInterface) {
            return $this->resolved;
        }

        $key = (string) config('ai.default_provider', 'mock');
        $provider = $this->make($key);

        if (! $provider->isAvailable()) {
            if ($key !== 'mock') {
                Log::warning("AI provider [{$key}] is unavailable (missing credentials); falling back to mock.");
            }
            $provider = new MockProvider;
        }

        return $this->resolved = $provider;
    }

    private function make(string $key): AIProviderInterface
    {
        return match ($key) {
            'openai' => new OpenAiProvider,
            default => new MockProvider,
        };
    }
}
