<?php

namespace App\Services\AiChat;

class PromptBuilder
{
    private string $systemPrompt;
    private array $history;
    private string $userMessage;

    public function __construct(string $systemPrompt)
    {
        $this->systemPrompt = $systemPrompt;
    }

    public function addHistory(array $history): self
    {
        $this->history = $history;
        return $this;
    }

    public function addUserMessage(string $userMessage): self
    {
        $this->userMessage = $userMessage;
        return $this;
    }
    // 
    public function build(): string
    {
        $prompt = "";
        $prompt .= "System: " . $this->systemPrompt . "\n";
        if (!empty($this->history)) {
            $prompt .= "History:\n";
            foreach ($this->history as $message) {
                $prompt .= $message->role . ": " . $message->content . "\n";
            }
        }
        $prompt .= "User: " . $this->userMessage . "\n";
        return $prompt;
    }

}