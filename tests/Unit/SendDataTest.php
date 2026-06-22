<?php

use App\DTOs\SendData;

it('includes the id in the attribute array when present', function () {
    $validTo = now()->addDay();

    $data = new SendData(
        userId: 1,
        message: 'secret',
        name: 'My Send',
        validTo: $validTo,
        id: '01JABCDEF',
    );

    expect($data->toArray())->toBe([
        'id' => '01JABCDEF',
        'user_id' => 1,
        'message' => 'secret',
        'name' => 'My Send',
        'valid_to' => $validTo,
    ]);
});

it('omits the id from the attribute array when null', function () {
    $validTo = now()->addDay();

    $data = new SendData(
        userId: 7,
        message: 'secret',
        name: 'My Send',
        validTo: $validTo,
    );

    expect($data->toArray())
        ->not->toHaveKey('id')
        ->toBe([
            'user_id' => 7,
            'message' => 'secret',
            'name' => 'My Send',
            'valid_to' => $validTo,
        ]);
});
