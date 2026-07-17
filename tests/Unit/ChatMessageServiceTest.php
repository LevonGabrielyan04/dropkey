<?php

use App\Models\User;
use App\Repositories\Interfaces\ChatMessageRepositoryInterface;
use App\Services\ChatMessageService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function () {
    Mockery::close();
});

it('rejects storing a message when sender and recipient are the same person', function () {
    $user = User::factory()->make(['id' => 1]);
    $repository = Mockery::mock(ChatMessageRepositoryInterface::class);
    $repository->shouldNotReceive('findOrCreateConversation');
    $repository->shouldNotReceive('createMessage');

    $service = new ChatMessageService($repository);

    $service->storeMessage($user, $user, fakeChatPayload());
})->throws(ValidationException::class);

it('includes a recipient_id validation error when sender and recipient are the same person', function () {
    $user = User::factory()->make(['id' => 1]);
    $repository = Mockery::mock(ChatMessageRepositoryInterface::class);
    $service = new ChatMessageService($repository);

    try {
        $service->storeMessage($user, $user, fakeChatPayload());
        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('recipient_id')
            ->and($exception->errors()['recipient_id'])->not->toBeEmpty();
    }
});

it('rejects fetching messages when both users are the same person', function () {
    $user = User::factory()->make(['id' => 1]);
    $repository = Mockery::mock(ChatMessageRepositoryInterface::class);
    $repository->shouldNotReceive('findConversationBetweenUsers');
    $repository->shouldNotReceive('getMessagesForConversation');

    $service = new ChatMessageService($repository);

    $service->getMessagesForUsers($user, $user);
})->throws(ValidationException::class);

it('includes a user validation error when fetching messages for the same person', function () {
    $user = User::factory()->make(['id' => 1]);
    $repository = Mockery::mock(ChatMessageRepositoryInterface::class);
    $service = new ChatMessageService($repository);

    try {
        $service->getMessagesForUsers($user, $user);
        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('user')
            ->and($exception->errors()['user'])->not->toBeEmpty();
    }
});
