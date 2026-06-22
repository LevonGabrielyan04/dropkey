<?php

use App\Models\User;
use App\Services\Interfaces\SendServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(SendServiceInterface::class);
});

it('shows a send resolved by its public id to an authorized viewer', function () {
    $author = User::factory()->create();
    $viewer = User::factory()->create();
    $this->actingAs($author);

    $send = $this->service->createSend([
        'name' => 'My Secret',
        'message' => 'top secret',
        'expire_after' => '1 day',
        'viewers' => [$viewer->email],
    ]);

    $minLength = config('send.password.min_length');

    $this->actingAs($viewer)
        ->get(route('sends.show', $send))
        ->assertOk()
        ->assertViewIs('sends.show')
        ->assertViewHas('send', fn ($value) => $value->is($send))
        ->assertSee('My Secret')
        ->assertSee($send->public_id)
        ->assertSee('top secret')
        ->assertSee($viewer->email)
        ->assertSee('x-data="sendDetailsManager"', false)
        ->assertSee('data-raw-message=', false)
        ->assertSee('data-min-password-length="'.$minLength.'"', false)
        ->assertSee('Decryption in progress', false)
        ->assertSee('This message is password protected', false)
        ->assertDontSee("sendDetailsManager('top secret', {$minLength})", false);
});

it('shows the decryption UI for password-protected sends', function () {
    $author = User::factory()->create();
    $viewer = User::factory()->create();
    $this->actingAs($author);

    $encryptedMessage = json_encode([
        'ciphertext' => 'encrypted-payload',
        'salt' => 'salt-value',
        'iv' => 'iv-value',
    ]);

    $send = $this->service->createSend([
        'name' => 'Locked Send',
        'message' => $encryptedMessage,
        'expire_after' => '1 day',
        'viewers' => [$viewer->email],
    ]);

    $minLength = config('send.password.min_length');

    $this->actingAs($viewer)
        ->get(route('sends.show', $send))
        ->assertOk()
        ->assertSee('Locked Send')
        ->assertSee($viewer->email)
        ->assertSee('This message is password protected', false)
        ->assertSee('Decryption in progress', false)
        ->assertSee('x-data="sendDetailsManager"', false)
        ->assertSee('data-raw-message=', false)
        ->assertSee('data-min-password-length="'.$minLength.'"', false)
        ->assertSee(':disabled="isDecrypting"', false)
        ->assertSee('Decrypt', false)
        ->assertDontSee('sendDetailsManager(', false);
});

it('forbids viewing a send for an unauthorized user', function () {
    $author = User::factory()->create();
    $stranger = User::factory()->create();
    $this->actingAs($author);

    $send = $this->service->createSend([
        'name' => 'My Secret',
        'message' => 'top secret',
        'expire_after' => '1 day',
        'viewers' => [],
    ]);

    $this->actingAs($stranger)
        ->get(route('sends.show', $send))
        ->assertNotFound();
});
