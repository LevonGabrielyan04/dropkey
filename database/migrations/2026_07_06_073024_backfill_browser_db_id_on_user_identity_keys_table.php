<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\BinaryCodec;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('user_identity_keys')
            ->whereNull('browser_db_id')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $identityKey): void {
                DB::table('user_identity_keys')
                    ->where('id', $identityKey->id)
                    ->update([
                        'browser_db_id' => BinaryCodec::encode((string) Str::ulid(), 'ulid'),
                    ]);
            });

        Schema::table('user_identity_keys', function (Blueprint $table): void {
            $table->binary('browser_db_id', length: 16, fixed: true)->nullable(false)->change();

            if (! $this->browserDbIdUniqueIndexExists()) {
                $table->unique('browser_db_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_identity_keys', function (Blueprint $table): void {
            if ($this->browserDbIdUniqueIndexExists()) {
                $table->dropUnique(['browser_db_id']);
            }

            $table->binary('browser_db_id', length: 16, fixed: true)->nullable()->change();
        });
    }

    private function browserDbIdUniqueIndexExists(): bool
    {
        return collect(Schema::getIndexes('user_identity_keys'))
            ->contains(fn (array $index): bool => in_array('browser_db_id', $index['columns'], true) && $index['unique']);
    }
};
