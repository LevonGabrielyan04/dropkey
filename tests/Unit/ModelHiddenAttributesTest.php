<?php

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Send;
use App\Models\User;
use App\Models\UserIdentityKey;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

uses(TestCase::class);

it('hides configured attributes from serialization', function (string $modelClass, array $hidden): void {
    /** @var Model $model */
    $model = new $modelClass;

    expect($model->getHidden())->toBe($hidden);
})->with([
    'user' => [User::class, ['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token']],
    'user identity key' => [UserIdentityKey::class, ['user_id', 'id']],
    'conversation' => [Conversation::class, ['user_one_id', 'user_two_id', 'id']],
    'chat message' => [ChatMessage::class, ['id']],
    'send' => [Send::class, ['user_id', 'id']],
]);
