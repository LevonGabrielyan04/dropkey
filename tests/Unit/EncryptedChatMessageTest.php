<?php

use App\Rules\EncryptedChatMessage;

function validateEncryptedChatMessage(mixed $value): array
{
    $failures = [];

    (new EncryptedChatMessage)->validate('payload', $value, function (string $message) use (&$failures): void {
        $failures[] = $message;
    });

    return $failures;
}

it('accepts a valid encrypted chat payload', function () {
    expect(validateEncryptedChatMessage(fakeChatPayload()))->toBeEmpty();
});

it('rejects invalid chat payloads', function (mixed $value) {
    expect(validateEncryptedChatMessage($value))->not->toBeEmpty();
})->with([
    'plain string' => 'top secret',
    'invalid json' => '{not-json',
    'missing version' => json_encode([
        'ciphertext' => base64_encode(random_bytes(8)),
        'iv' => base64_encode(random_bytes(12)),
    ]),
    'wrong version' => json_encode([
        'v' => 2,
        'ciphertext' => base64_encode(random_bytes(8)),
        'iv' => base64_encode(random_bytes(12)),
    ]),
    'missing ciphertext' => json_encode([
        'v' => 1,
        'iv' => base64_encode(random_bytes(12)),
    ]),
    'empty ciphertext' => json_encode([
        'v' => 1,
        'ciphertext' => '',
        'iv' => base64_encode(random_bytes(12)),
    ]),
    'invalid base64' => json_encode([
        'v' => 1,
        'ciphertext' => '!!!',
        'iv' => base64_encode(random_bytes(12)),
    ]),
    'wrong iv length' => json_encode([
        'v' => 1,
        'ciphertext' => base64_encode(random_bytes(8)),
        'iv' => base64_encode(random_bytes(8)),
    ]),
    'send-style payload with salt' => fakeEncryptedMessage(),
]);
