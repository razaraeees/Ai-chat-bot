<?php

namespace App\Services\AiChat;

use App\Models\Conversation;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class HistoryService
{
    public function getHistory(int $conversationId): array
    {
        $history = \App\Models\Message::where('conversation_id', $conversationId)
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        return $history->map(fn($m) => $m->role === 'user' ? new UserMessage($m->content) : new AssistantMessage($m->content))->all();
    }
    
}
