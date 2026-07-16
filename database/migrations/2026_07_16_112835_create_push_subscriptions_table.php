<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection($this->connectionName())->create($this->tableName(), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('subscribable', 'push_subscriptions_subscribable_morph_idx');
            $table->string('endpoint', 500)->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connectionName())->dropIfExists($this->tableName());
    }

    private function connectionName(): ?string
    {
        $connection = config('webpush.database_connection');

        return is_string($connection) && $connection !== '' ? $connection : null;
    }

    private function tableName(): string
    {
        $table = config('webpush.table_name');

        return is_string($table) && $table !== '' ? $table : 'push_subscriptions';
    }
};
