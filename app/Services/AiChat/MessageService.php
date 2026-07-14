<?php

namespace App\Services\AiChat;

use App\Models\Conversation;
use App\Models\Message;

class MessageService
{
    /**
     * Store a new message in the database for the given conversation.
     */
    public function createMessage(Conversation $conversation, string $role, string $content): Message
    {
        return $conversation->messages()->create([
            'role' => $role,
            'content' => $content,
        ]);
    }
}
