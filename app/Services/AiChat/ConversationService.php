<?php

namespace App\Services\AiChat;

use App\Models\Conversation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConversationService
{
    public function createConversation(?int $conversationId, string $message): Conversation
    {
        if ($conversationId) {
            return Conversation::findOrFail($conversationId);
        }

        return Conversation::create([
            'user_id' => auth()->id(),
            'title' => Str::limit($message, 50),
        ]);
    }

    

}
