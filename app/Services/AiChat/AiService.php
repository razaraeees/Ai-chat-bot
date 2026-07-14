<?php

namespace App\Services\AiChat;

use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Text\Response;

class AiService
{
    public function chat(array $history): Response
    {
        return Prism::text()
            ->using(Provider::OpenRouter, config('ai.model'))
            ->withMessages($history)
            ->withSystemPrompt(config('ai.system_prompt'))
            ->withMaxTokens((int) config('ai.max_tokens'))
            ->generate();
    }
}
