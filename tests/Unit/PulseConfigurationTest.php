<?php

use Laravel\Pulse\Recorders\UserJobs;
use Laravel\Pulse\Recorders\UserRequests;
use Tests\TestCase;

uses(TestCase::class);

test('pulse disables user-specific recorders by default', function () {
    expect(config('pulse.recorders')[UserRequests::class]['enabled'])->toBeFalse()
        ->and(config('pulse.recorders')[UserJobs::class]['enabled'])->toBeFalse();
});

test('pulse uses the array cache driver by default', function () {
    expect(config('pulse.cache'))->toBe('array');
});

test('env examples disable pulse user-specific recorders', function () {
    foreach (['.env.example', '.env.docker.example'] as $file) {
        $contents = file_get_contents(base_path($file));

        expect($contents)
            ->toContain('PULSE_CACHE_DRIVER=array')
            ->toContain('PULSE_USER_REQUESTS_ENABLED=false')
            ->toContain('PULSE_USER_JOBS_ENABLED=false');
    }
});
