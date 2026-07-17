<?php

use App\Support\Csp\StrictPolicyPreset;
use Spatie\Csp\Policy;

it('separates internal broadcast api options from public client options', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.options' => [
            'host' => '127.0.0.1',
            'port' => 8081,
            'scheme' => 'http',
            'useTLS' => false,
        ],
        'broadcasting.connections.reverb.client' => [
            'host' => 'dropkey.site',
            'port' => 443,
            'scheme' => 'https',
        ],
    ]);

    expect(config('broadcasting.connections.reverb.options.host'))->toBe('127.0.0.1')
        ->and(config('broadcasting.connections.reverb.client.host'))->toBe('dropkey.site');
});

it('renders runtime reverb config for the browser when broadcasting is enabled', function () {
    config([
        'app.url' => 'https://example.test',
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'public-reverb-key',
        'broadcasting.connections.reverb.client' => [
            'host' => 'example.test',
            'port' => 443,
            'scheme' => 'https',
        ],
    ]);

    $response = $this->get('/login');

    $response->assertSuccessful();

    expect($response->getContent())
        ->toContain('window.__reverbConfig')
        ->toContain('"key":"public-reverb-key"')
        ->toContain('"host":"example.test"')
        ->toContain('"port":443')
        ->toContain('"scheme":"https"');
});

it('does not render runtime reverb config when broadcasting is disabled', function () {
    config([
        'app.url' => 'https://example.test',
        'broadcasting.default' => 'null',
        'broadcasting.connections.reverb.key' => 'public-reverb-key',
        'broadcasting.connections.reverb.client' => [
            'host' => 'example.test',
            'port' => 443,
            'scheme' => 'https',
        ],
    ]);

    $response = $this->get('/login');

    $response->assertSuccessful();

    expect($response->getContent())->not->toContain('window.__reverbConfig');
});

it('allows public reverb websocket origins in the content security policy', function () {
    config([
        'app.url' => 'https://example.test',
        'app.env' => 'production',
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.client' => [
            'host' => 'example.test',
            'port' => 443,
            'scheme' => 'https',
        ],
    ]);

    $policy = Policy::create([StrictPolicyPreset::class])->getContents();

    expect($policy)
        ->toContain('wss://example.test:443')
        ->not->toContain('wss://127.0.0.1:8081');
});
