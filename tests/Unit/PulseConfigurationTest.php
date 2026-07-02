<?php

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

uses(TestCase::class);

test('pulse uses the array cache driver by default', function () {
    expect(config('pulse.cache'))->toBe('array');
});

test('cache allows pulse dashboard query results to be unserialized', function () {
    $store = Cache::store('file');

    $store->put('laravel:pulse:test', [
        collect([(object) ['key' => 'user-1', 'count' => 5]]),
        12.5,
        now()->toDateTimeString(),
    ], 60);

    $retrieved = $store->get('laravel:pulse:test');

    expect($retrieved[0])->toBeInstanceOf(Collection::class)
        ->and($retrieved[0]->first()->count)->toBe(5);
});

test('viewPulse gate allows the configured admin email when verified', function () {
    $user = User::factory()->make([
        'email' => config('pulse.admin_email'),
        'email_verified_at' => now(),
    ]);

    expect(Gate::forUser($user)->allows('viewPulse'))->toBeTrue();
});

test('viewPulse gate denies other users', function () {
    $user = User::factory()->make([
        'email' => 'other@example.com',
        'email_verified_at' => now(),
    ]);

    expect(Gate::forUser($user)->allows('viewPulse'))->toBeFalse();
});
