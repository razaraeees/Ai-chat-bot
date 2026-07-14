<?php

namespace App\Http\Controllers;

use App\Http\Requests\AiChatRequest;
use App\Models\Conversation;
use App\Services\AiChat\ConversationService;
use App\Services\AiChat\HistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\AiChat\AiService;
use App\Services\AiChat\MessageService;

class AiChatController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly HistoryService $historyService,
        private readonly AiService $aiService,
        private readonly MessageService $messageService
    ) {}

    public function __invoke(AiChatRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $message = trim($validated['message']);

        // Find existing conversation, or start a new one
        $conversation = $this->conversationService->createConversation($request->conversation_id, $message);

        try {
            // Save the user's message first
            $this->messageService->createMessage($conversation, 'user', $message);

            $history = $this->historyService->getHistory($conversation->id);
            Log::info(
                collect($history)->map(function ($message) {

                    return [
                        'class' => get_class($message),
                        'content' => $message->content,
                    ];
                })->toArray()
            );
            $response = $this->aiService->chat($history);

            // Save AI's reply too
            $this->messageService->createMessage($conversation, 'assistant', $response->text);

            return response()->json([
                'reply' => $response->text,
                'conversation_id' => $conversation->id,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'reply' => 'AI response generation failed: ' . $e->getMessage(),
            ], 200);
        }
    }
    public function history(Conversation $conversation): JsonResponse
    {
        // Security: only the conversation's owner can read it
        if ($conversation->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $messages = \App\Models\Message::where('conversation_id', $conversation->id)
            ->orderBy('id', 'asc')
            ->get(['id', 'role', 'content', 'created_at']);

        return response()->json([
            'messages' => $messages,
        ]);
    }
}
