<?php

use App\Support\Csp\DisablesCspForPulse;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

it('disables csp for pulse dashboard routes', function (string $uri) {
    config(['pulse.path' => 'pulse', 'pulse.domain' => null]);

    $request = Request::create($uri);

    expect(DisablesCspForPulse::matches($request))->toBeTrue();
})->with([
    '/pulse',
    '/pulse/',
    '/pulse?period=1_hour',
]);

it('does not disable csp for non pulse routes', function () {
    config(['pulse.path' => 'pulse', 'pulse.domain' => null]);

    $request = Request::create('/login');

    expect(DisablesCspForPulse::matches($request))->toBeFalse();
});

it('only disables csp on the configured pulse domain when set', function () {
    config(['pulse.path' => 'pulse', 'pulse.domain' => 'pulse.example.test']);

    expect(DisablesCspForPulse::matches(Request::create('https://pulse.example.test/pulse')))->toBeTrue()
        ->and(DisablesCspForPulse::matches(Request::create('https://example.test/pulse')))->toBeFalse();
});

it('respects a custom pulse path', function () {
    config(['pulse.path' => 'monitoring', 'pulse.domain' => null]);

    expect(DisablesCspForPulse::matches(Request::create('/monitoring')))->toBeTrue()
        ->and(DisablesCspForPulse::matches(Request::create('/pulse')))->toBeFalse();
});
