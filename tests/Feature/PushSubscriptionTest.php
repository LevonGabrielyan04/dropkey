<?php

use App\Models\User;
use NotificationChannels\WebPush\PushSubscription;

it('returns the vapid public key', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.push.vapid-public-key'))
        ->assertSuccessful()
        ->assertJson([
            'public_key' => config('webpush.vapid.public_key'),
        ]);
});

it('stores a push subscription for the authenticated user', function () {
    $user = User::factory()->create();
    $endpoint = 'https://fcm.googleapis.com/fcm/send/store-endpoint';

    $this->actingAs($user)
        ->postJson(route('api.push-subscriptions.store'), [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => 'p256dh-test-key',
                'auth' => 'auth-test-token',
            ],
            'content_encoding' => 'aes128gcm',
        ])
        ->assertCreated()
        ->assertJson(['status' => 'ok']);

    $subscription = PushSubscription::query()->where('endpoint', $endpoint)->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->public_key)->toBe('p256dh-test-key')
        ->and($subscription->auth_token)->toBe('auth-test-token')
        ->and($subscription->content_encoding->value)->toBe('aes128gcm')
        ->and($user->ownsPushSubscription($subscription))->toBeTrue();
});

it('updates an existing push subscription owned by the user', function () {
    $user = User::factory()->create();
    $endpoint = 'https://fcm.googleapis.com/fcm/send/update-endpoint';

    $user->updatePushSubscription($endpoint, 'old-key', 'old-token', 'aes128gcm');

    $this->actingAs($user)
        ->postJson(route('api.push-subscriptions.store'), [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => 'new-key',
                'auth' => 'new-token',
            ],
            'content_encoding' => 'aes128gcm',
        ])
        ->assertCreated();

    expect(PushSubscription::query()->where('endpoint', $endpoint)->count())->toBe(1)
        ->and(PushSubscription::query()->where('endpoint', $endpoint)->value('public_key'))->toBe('new-key')
        ->and(PushSubscription::query()->where('endpoint', $endpoint)->value('auth_token'))->toBe('new-token');
});

it('deletes a push subscription for the authenticated user', function () {
    $user = User::factory()->create();
    $endpoint = 'https://fcm.googleapis.com/fcm/send/delete-endpoint';

    $user->updatePushSubscription($endpoint, 'p256dh-test-key', 'auth-test-token', 'aes128gcm');

    $this->actingAs($user)
        ->deleteJson(route('api.push-subscriptions.destroy'), [
            'endpoint' => $endpoint,
        ])
        ->assertSuccessful()
        ->assertJson(['status' => 'ok']);

    expect(PushSubscription::query()->where('endpoint', $endpoint)->exists())->toBeFalse();
});

it('requires authentication for push subscription endpoints', function () {
    $this->getJson(route('api.push.vapid-public-key'))->assertUnauthorized();

    $this->postJson(route('api.push-subscriptions.store'), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/guest-endpoint',
        'keys' => [
            'p256dh' => 'p256dh-test-key',
            'auth' => 'auth-test-token',
        ],
    ])->assertUnauthorized();

    $this->deleteJson(route('api.push-subscriptions.destroy'), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/guest-endpoint',
    ])->assertUnauthorized();
});

it('validates push subscription payloads', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.push-subscriptions.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['endpoint', 'keys.p256dh', 'keys.auth']);

    $this->actingAs($user)
        ->deleteJson(route('api.push-subscriptions.destroy'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['endpoint']);
});
