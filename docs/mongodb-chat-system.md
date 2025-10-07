# MongoDB Chat System Documentation

## Overview

The Shoot90 chat system uses MongoDB for storing chat data, providing a flexible schema that supports various message types, reactions, and real-time features. The system integrates with Laravel's broadcasting system and Reverb for real-time messaging capabilities.

## Architecture

### Database Structure

The chat system uses two primary MongoDB collections:

1. **Conversations** - Stores information about chat conversations
2. **Messages** - Stores the actual message content and metadata

### Models

#### MongoConversation

The `MongoConversation` model represents a chat conversation between two or more users. It includes:

- `participants`: Array of user IDs
- `last_message`: Information about the most recent message
- `unread_counts`: Object mapping user IDs to their unread message counts

```php
// Example of creating a new conversation
$conversation = new MongoConversation();
$conversation->participants = ['user1_id', 'user2_id'];
$conversation->save();
```

#### MongoMessage

The `MongoMessage` model represents an individual message within a conversation. It includes:

- `conversation_id`: Reference to the conversation
- `sender_id`: ID of the user who sent the message
- `content`: Text content of the message
- `type`: Type of message (text, image, video, audio, etc.)
- `media_url`: URL to media content (for non-text messages)
- `thumbnail_url`: URL to thumbnail (for video/image messages)
- `duration`: Duration in seconds (for audio/video messages)
- `is_read`: Boolean indicating if the message has been read
- `read_at`: Timestamp when the message was read
- `reactions`: Array of user reactions to the message

```php
// Example of creating a new message
$message = new MongoMessage();
$message->conversation_id = 'conversation_id';
$message->sender_id = 'user_id';
$message->content = 'Hello, world!';
$message->type = 'text';
$message->is_read = false;
$message->save();
```

### Cross-Database Relationships

Since the chat system uses MongoDB while the user system uses SQL, we implement custom relationship methods to handle cross-database relationships. This avoids the "Call to a member function prepare() on null" errors that occur when trying to join across different database types.

```php
// Example of retrieving a user from a MongoDB message
$message = MongoMessage::find('message_id');
$sender = $message->getSender(); // Custom method to fetch the related SQL User model
```

## Real-Time Features

### Events

The chat system includes three primary events for real-time updates:

1. **MessageSent** - Triggered when a new message is sent
2. **MessageRead** - Triggered when a message is marked as read
3. **UserTyping** - Triggered when a user starts or stops typing

### GraphQL Subscriptions

The system uses GraphQL subscriptions through Reverb to provide real-time updates to clients:

```graphql
# Subscribe to new messages in a conversation
subscription MessageSent($conversationId: ID!) {
  messageSent(conversationId: $conversationId) {
    id
    content
    sender_id
    # other fields...
  }
}

# Subscribe to typing status updates
subscription UserTyping($conversationId: ID!) {
  userTyping(conversationId: $conversationId) {
    user_id
    is_typing
    timestamp
  }
}
```

## GraphQL API

### Queries

- `getConversations` - Get a list of the user's conversations
- `getMessages` - Get messages for a specific conversation
- `searchMessages` - Search for messages within a conversation

### Mutations

- `startConversation` - Start a new conversation with another user
- `sendMessage` - Send a text message
- `sendMediaMessage` - Send a media message (image, video, audio)
- `markMessageAsRead` - Mark a message as read
- `markConversationAsRead` - Mark all messages in a conversation as read
- `addReaction` - Add a reaction to a message
- `removeReaction` - Remove a reaction from a message
- `deleteMessage` - Delete a message
- `setTypingStatus` - Update typing status

## Usage Examples

### Starting a Conversation

```graphql
mutation {
  startConversation(receiver_id: "123") {
    id
    participants
  }
}
```

### Sending a Message

```graphql
mutation {
  sendMessage(input: {
    conversation_id: "conversation_id",
    content: "Hello, how are you?"
  }) {
    id
    content
    created_at
  }
}
```

### Sending a Media Message

```graphql
mutation {
  sendMediaMessage(input: {
    conversation_id: "conversation_id",
    media_content: "base64_encoded_media",
    type: "image"
  }) {
    id
    type
    media_url
  }
}
```

### Subscribing to New Messages

```graphql
subscription {
  messageSent(conversationId: "conversation_id") {
    id
    content
    sender {
      id
      name
    }
    created_at
  }
}
```

## Security Considerations

- Users can only access conversations they are participants in
- Users can only delete their own messages or unread messages
- All GraphQL mutations require authentication
- Subscription channels are secured to only allow authorized participants

## Performance Optimization

- Indexes are created on frequently queried fields
- Pagination is implemented for conversation and message lists
- Unread counts are stored at the conversation level for quick access
- Media files are stored separately with only references in the database
