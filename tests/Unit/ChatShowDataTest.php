<?php

use App\DTOs\ChatShowData;
use App\Enums\TimePeriod;
use App\Models\Conversation;
use App\Models\User;

it('defaults auto-delete to seven days when there is no conversation', function () {
    $recipient = new User;

    $data = ChatShowData::from($recipient, null);

    expect($data->recipient)->toBe($recipient)
        ->and($data->conversation)->toBeNull()
        ->and($data->autoDelete)->toBe(TimePeriod::SEVEN_DAYS)
        ->and($data->timePeriods)->toBe(TimePeriod::cases());
});

it('uses the conversation auto-delete setting when present', function () {
    $recipient = new User;
    $conversation = new Conversation(['auto_delete' => TimePeriod::ONE_DAY]);

    $data = ChatShowData::from($recipient, $conversation);

    expect($data->conversation)->toBe($conversation)
        ->and($data->autoDelete)->toBe(TimePeriod::ONE_DAY);
});

it('converts to a view-ready array', function () {
    $recipient = new User;
    $conversation = new Conversation(['auto_delete' => TimePeriod::SIX_HOURS]);

    $data = ChatShowData::from($recipient, $conversation);

    expect($data->toArray())->toBe([
        'recipient' => $recipient,
        'conversation' => $conversation,
        'autoDelete' => TimePeriod::SIX_HOURS,
        'timePeriods' => TimePeriod::cases(),
    ]);
});

it('cannot modify properties after construction', function () {
    $data = ChatShowData::from(new User, null);

    $data->autoDelete = TimePeriod::ONE_HOUR;
})->throws(Error::class);
