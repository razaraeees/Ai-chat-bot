<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Tests\TestCase;

class AiChatControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_chat_message_and_receive_ai_response()
    {
        $user = User::factory()->create();

        $fakeResponse = TextResponseFake::make()
            ->withText('Hello, I am Nexus AI!');

        Prism::fake([$fakeResponse]);

        $response = $this->actingAs($user)
            ->postJson(route('ai.chat'), [
                'message' => 'Hello AI',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'reply',
                'conversation_id',
            ]);

        $conversationId = $response->json('conversation_id');

        // Assert database has both the user message and the assistant message
        $this->assertDatabaseHas('conversations', [
            'id' => $conversationId,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversationId,
            'role' => 'user',
            'content' => 'Hello AI',
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversationId,
            'role' => 'assistant',
            'content' => 'Hello, I am Nexus AI!',
        ]);
    }

    public function test_existing_conversation_retains_history_in_order()
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'Previous Chat',
        ]);

        // Create some messages
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'First message',
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'First reply',
        ]);

        $fakeResponse = TextResponseFake::make()
            ->withText('This is the second reply.');

        Prism::fake([$fakeResponse]);

        $response = $this->actingAs($user)
            ->postJson(route('ai.chat'), [
                'message' => 'Second message',
                'conversation_id' => $conversation->id,
            ]);

        $response->assertOk();

        // Check if the history endpoint returns all 4 messages in chronological order
        $historyResponse = $this->actingAs($user)
            ->getJson("/ai/conversations/{$conversation->id}/messages");

        $historyResponse->assertOk()
            ->assertJsonCount(4, 'messages');

        $messages = $historyResponse->json('messages');
        
        $this->assertEquals('First message', $messages[0]['content']);
        $this->assertEquals('user', $messages[0]['role']);

        $this->assertEquals('First reply', $messages[1]['content']);
        $this->assertEquals('assistant', $messages[1]['role']);

        $this->assertEquals('Second message', $messages[2]['content']);
        $this->assertEquals('user', $messages[2]['role']);

        $this->assertEquals('This is the second reply.', $messages[3]['content']);
        $this->assertEquals('assistant', $messages[3]['role']);
    }
}
