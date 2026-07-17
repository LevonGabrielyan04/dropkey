<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

it('allows guests to visit the welcome page', function () {
    $this->get(route('home'))->assertSuccessful();
});

it('allows guests to visit the login page', function () {
    $this->get(route('login'))->assertSuccessful();
});

it('allows guests to fetch passkey discovery endpoints', function () {
    $this->get(route('well-known.passkeys'))
        ->assertSuccessful()
        ->assertJsonStructure(['enroll', 'manage']);
});

it('requires authentication for application routes defined in web.php', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with([
    'dashboard',
    'chat.index',
    'profile.edit',
    'notifications.edit',
]);

it('applies auth middleware from the web group except opted-out and guest routes', function () {
    $router = app(Router::class);
    $routes = Route::getRoutes();

    $homeMiddleware = $router->gatherRouteMiddleware($routes->getByName('home'));
    $passkeysMiddleware = $router->gatherRouteMiddleware($routes->getByName('well-known.passkeys'));
    $loginMiddleware = $router->gatherRouteMiddleware($routes->getByName('login'));
    $dashboardMiddleware = $router->gatherRouteMiddleware($routes->getByName('dashboard'));

    expect($homeMiddleware)->not->toContain(Authenticate::class)
        ->and($passkeysMiddleware)->not->toContain(Authenticate::class)
        ->and($loginMiddleware)->not->toContain(Authenticate::class)
        ->and($dashboardMiddleware)->toContain(Authenticate::class);
});

it('applies auth middleware to newly registered web routes by default', function () {
    $route = Route::middleware('web')
        ->get('/__temporary-auth-check', fn (): string => 'ok')
        ->name('temporary.auth.check');

    $middleware = app(Router::class)->gatherRouteMiddleware($route);

    expect($middleware)->toContain(Authenticate::class);

    $this->get('/__temporary-auth-check')->assertRedirect(route('login'));
});
