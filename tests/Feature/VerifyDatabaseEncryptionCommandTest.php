<?php

use App\Support\Database\TransparentDataEncryptionVerifier;
use Illuminate\Support\Facades\DB;

it('skips encryption verification for non-mysql drivers', function () {
    config(['database.default' => 'sqlite']);

    $this->artisan('db:verify-encryption')
        ->expectsOutputToContain('Transparent data encryption checks apply only to MariaDB/MySQL connections.')
        ->assertSuccessful();
});

it('reports success when mariadb transparent data encryption is fully enabled', function () {
    $verifier = Mockery::mock(TransparentDataEncryptionVerifier::class);
    $verifier->shouldReceive('supportsTransparentDataEncryption')->once()->andReturn(true);
    $verifier->shouldReceive('issues')->once()->andReturn([]);

    $this->app->instance(TransparentDataEncryptionVerifier::class, $verifier);

    $this->artisan('db:verify-encryption')
        ->expectsOutputToContain('Database transparent data encryption is fully enabled.')
        ->assertSuccessful();
});

it('fails when encryption is incomplete and fail-on-error is set', function () {
    $verifier = Mockery::mock(TransparentDataEncryptionVerifier::class);
    $verifier->shouldReceive('supportsTransparentDataEncryption')->once()->andReturn(true);
    $verifier->shouldReceive('issues')->once()->andReturn([
        'The file_key_management plugin is not loaded.',
    ]);

    $this->app->instance(TransparentDataEncryptionVerifier::class, $verifier);

    $this->artisan('db:verify-encryption --fail-on-error')
        ->expectsOutputToContain('The file_key_management plugin is not loaded.')
        ->assertFailed();
});

it('detects when the key management plugin is not loaded', function () {
    DB::shouldReceive('connection->getDriverName')->andReturn('mariadb');
    DB::shouldReceive('selectOne')
        ->once()
        ->with(
            Mockery::type('string'),
            ['file_key_management'],
        )
        ->andReturn((object) ['status' => 'DISABLED']);

    $verifier = new TransparentDataEncryptionVerifier;

    expect($verifier->keyManagementPluginIsLoaded())->toBeFalse();
});

it('detects unencrypted innodb tablespaces', function () {
    DB::shouldReceive('connection->getDriverName')->andReturn('mariadb');
    DB::shouldReceive('selectOne')
        ->with(Mockery::type('string'), ['information_schema', 'INNODB_TABLESPACES_ENCRYPTION'])
        ->andReturn((object) ['name' => 'INNODB_TABLESPACES_ENCRYPTION']);
    DB::shouldReceive('select')
        ->once()
        ->with(Mockery::type('string'), ['mysql/%'])
        ->andReturn([
            (object) ['NAME' => 'passshare/users'],
        ]);

    $verifier = new TransparentDataEncryptionVerifier;

    expect($verifier->unencryptedTablespaces()->all())->toBe(['passshare/users']);
});

it('treats sqlite as fully encrypted for verifier convenience', function () {
    config(['database.default' => 'sqlite']);

    $verifier = new TransparentDataEncryptionVerifier;

    expect($verifier->supportsTransparentDataEncryption())->toBeFalse()
        ->and($verifier->isFullyEncrypted())->toBeTrue()
        ->and($verifier->issues())->toBe([]);
});
