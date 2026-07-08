<?php

use App\Actions\DeleteExpiredSendsAction;
use App\Models\Send;
use App\Models\User;
use App\Services\Interfaces\SendReadServiceInterface;
use App\Support\SendIndexColumns;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Tests\Factories\SendFactory;

it('deletes expired sends when the command is run', function () {
    $user = User::factory()->create();

    SendFactory::create($user, [
        'message' => 'expired secret',
        'name' => 'Expired Send',
        'valid_to' => now()->subMinute(),
    ]);

    SendFactory::create($user, [
        'message' => 'active secret',
        'name' => 'Active Send',
    ]);

    $this->artisan('sends:delete-expired')
        ->expectsOutputToContain('Deleted 1 expired send(s).')
        ->assertSuccessful();

    expect(Send::query()->count())->toBe(1)
        ->and(Send::query()->where('name', 'Expired Send')->exists())->toBeFalse()
        ->and(Send::query()->where('name', 'Active Send')->exists())->toBeTrue();
});

it('schedules expired send deletion every thirty minutes', function () {
    $this->artisan('schedule:list');

    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command ?? '', 'sends:delete-expired'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('*/30 * * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
});

it('clears cached send lists when expired sends are deleted', function () {
    $user = User::factory()->create();

    SendFactory::create($user, [
        'message' => 'active secret',
        'name' => 'Active Send',
    ]);

    $this->actingAs($user);
    app(SendReadServiceInterface::class)->findAll();

    $cacheKey = 'sends_'.$user->id.'_'.hash('xxh128', json_encode(array_values(SendIndexColumns::COLUMNS)));
    expect(Cache::get($cacheKey))->toHaveCount(1);

    SendFactory::create($user, [
        'message' => 'expired secret',
        'name' => 'Expired Send',
        'valid_to' => now()->subMinute(),
    ]);

    app(DeleteExpiredSendsAction::class)->execute();

    expect(Cache::get($cacheKey))->toBeNull()
        ->and(app(SendReadServiceInterface::class)->findAll())->toHaveCount(1);
});
