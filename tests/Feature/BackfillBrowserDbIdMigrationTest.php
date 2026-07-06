<?php

use App\Models\User;
use App\Models\UserIdentityKey;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

const BACKFILL_MIGRATION = 'database/migrations/2026_07_06_073024_backfill_browser_db_id_on_user_identity_keys_table.php';

it('backfills browser database ids for existing identity keys', function () {
    $user = User::factory()->create();

    UserIdentityKey::query()->create([
        'user_id' => $user->id,
        'public_key_jwk' => validPublicKeyPayload()['public_key_jwk'],
        'fingerprint' => validPublicKeyPayload()['fingerprint'],
    ]);

    Artisan::call('migrate:rollback', ['--path' => BACKFILL_MIGRATION]);

    DB::table('user_identity_keys')->update(['browser_db_id' => null]);

    expect(DB::table('user_identity_keys')->value('browser_db_id'))->toBeNull();

    Artisan::call('migrate', ['--path' => BACKFILL_MIGRATION]);

    $browserDbId = UserIdentityKey::query()->value('browser_db_id');

    expect($browserDbId)->not->toBeEmpty()
        ->and(Str::isUlid($browserDbId))->toBeTrue();

    $indexes = collect(Schema::getIndexes('user_identity_keys'));

    expect($indexes->contains(
        fn (array $index): bool => in_array('browser_db_id', $index['columns'], true) && $index['unique'],
    ))->toBeTrue();
});
