<?php

namespace Tests\Feature;

use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use WithFaker;

    protected $sender;
    protected $receiver;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->sender = User::factory()->create();
        $this->receiver = User::factory()->create();
    }

    /**
     * Test starting a new conversation
     */
    public function test_can_start_conversation()
    {
        $response = $this->actingAs($this->sender)
            ->postJson('/graphql', [
                'query' => '
                    mutation StartConversation($receiverId: ID!) {
                        startConversation(receiver_id: $receiverId) {
                            id
                            participants
                        }
                    }
                ',
                'variables' => [
                    'receiverId' => (string) $this->receiver->id,
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'startConversation' => [
                        'id',
                        'participants',
                    ],
                ],
            ]);

        // Verify the conversation exists in MongoDB
        $conversationId = $response->json('data.startConversation.id');
        $conversation = Conversation::find($conversationId);
        $this->assertNotNull($conversation);
        $this->assertTrue(in_array((string) $this->sender->id, $conversation->participants));
        $this->assertTrue(in_array((string) $this->receiver->id, $conversation->participants));
    }

    /**
     * Test sending a text message
     */
    public function test_can_send_text_message()
    {
        // First create a conversation
        $conversation = new Conversation();
        $conversation->participants = [(string) $this->sender->id, (string) $this->receiver->id];
        $conversation->save();

        $messageContent = $this->faker->sentence();

        $response = $this->actingAs($this->sender)
            ->postJson('/graphql', [
                'query' => '
                    mutation SendMessage($input: ChatMessageInput!) {
                        sendMessage(input: $input) {
                            id
                            conversation_id
                            sender_id
                            content
                            type
                            is_read
                        }
                    }
                ',
                'variables' => [
                    'input' => [
                        'conversation_id' => (string) $conversation->_id,
                        'content' => $messageContent,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'sendMessage' => [
                        'id',
                        'conversation_id',
                        'sender_id',
                        'content',
                        'type',
                        'is_read',
                    ],
                ],
            ]);

        // Verify the message exists in MongoDB
        $messageId = $response->json('data.sendMessage.id');
        $message = Message::find($messageId);
        $this->assertNotNull($message);
        $this->assertEquals($messageContent, $message->content);
        $this->assertEquals('text', $message->type);
        $this->assertEquals((string) $this->sender->id, $message->sender_id);
    }

    /**
     * Test marking a message as read
     */
    public function test_can_mark_message_as_read()
    {
        // Create a conversation
        $conversation = new Conversation();
        $conversation->participants = [(string) $this->sender->id, (string) $this->receiver->id];
        $conversation->save();

        // Create a message
        $message = new Message();
        $message->conversation_id = (string) $conversation->_id;
        $message->sender_id = (string) $this->sender->id;
        $message->content = $this->faker->sentence();
        $message->type = 'text';
        $message->is_read = false;
        $message->save();

        // Mark the message as read
        $response = $this->actingAs($this->receiver)
            ->postJson('/graphql', [
                'query' => '
                    mutation MarkMessageAsRead($messageId: ID!) {
                        markMessageAsRead(message_id: $messageId) {
                            success
                            message
                        }
                    }
                ',
                'variables' => [
                    'messageId' => (string) $message->_id,
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'markMessageAsRead' => [
                        'success' => true,
                    ],
                ],
            ]);

        // Verify the message is marked as read
        $message->refresh();
        $this->assertTrue($message->is_read);
        $this->assertNotNull($message->read_at);
    }

    /**
     * Test adding a reaction to a message
     */
    public function test_can_add_reaction_to_message()
    {
        // Create a conversation
        $conversation = new Conversation();
        $conversation->participants = [(string) $this->sender->id, (string) $this->receiver->id];
        $conversation->save();

        // Create a message
        $message = new Message();
        $message->conversation_id = (string) $conversation->_id;
        $message->sender_id = (string) $this->sender->id;
        $message->content = $this->faker->sentence();
        $message->type = 'text';
        $message->is_read = false;
        $message->save();

        // Add a reaction
        $reaction = '❤️';
        $response = $this->actingAs($this->receiver)
            ->postJson('/graphql', [
                'query' => '
                    mutation AddReaction($messageId: ID!, $reaction: String!) {
                        addReaction(message_id: $messageId, reaction: $reaction) {
                            success
                            message
                        }
                    }
                ',
                'variables' => [
                    'messageId' => (string) $message->_id,
                    'reaction' => $reaction,
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'addReaction' => [
                        'success' => true,
                    ],
                ],
            ]);

        // Verify the reaction was added
        $message->refresh();
        $this->assertNotEmpty($message->reactions);
        $this->assertEquals($reaction, $message->reactions[0]['reaction']);
        $this->assertEquals((string) $this->receiver->id, $message->reactions[0]['user_id']);
    }

    /**
     * Test deleting a message
     */
    public function test_can_delete_message()
    {
        // Create a conversation
        $conversation = new Conversation();
        $conversation->participants = [(string) $this->sender->id, (string) $this->receiver->id];
        $conversation->save();

        // Create a message
        $message = new Message();
        $message->conversation_id = (string) $conversation->_id;
        $message->sender_id = (string) $this->sender->id;
        $message->content = $this->faker->sentence();
        $message->type = 'text';
        $message->is_read = false;
        $message->save();

        // Delete the message
        $response = $this->actingAs($this->sender)
            ->postJson('/graphql', [
                'query' => '
                    mutation DeleteMessage($messageId: ID!) {
                        deleteMessage(message_id: $messageId) {
                            success
                            message
                        }
                    }
                ',
                'variables' => [
                    'messageId' => (string) $message->_id,
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'deleteMessage' => [
                        'success' => true,
                    ],
                ],
            ]);

        // Verify the message was deleted
        $this->assertNull(Message::find($message->_id));
    }
}
