<?php

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;

it('deletes expired chat messages when the command is run', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $conversation = createConversation($alice, $bob);

    $expiredMessage = ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'payload' => fakeChatPayload(),
    ]);

    ChatMessage::query()
        ->whereKey($expiredMessage->id)
        ->update(['created_at' => now()->subHours(25)]);

    ChatMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $bob->id,
        'payload' => fakeChatPayload(),
    ]);

    $this->artisan('chat-messages:delete-expired')
        ->expectsOutputToContain('Deleted 1 expired chat message(s).')
        ->assertSuccessful();

    expect(ChatMessage::query()->count())->toBe(1)
        ->and(ChatMessage::query()->whereKey($expiredMessage->id)->exists())->toBeFalse();
});

it('schedules expired chat message deletion every thirty minutes', function () {
    $this->artisan('schedule:list');

    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command ?? '', 'chat-messages:delete-expired'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('*/30 * * * *');
});
