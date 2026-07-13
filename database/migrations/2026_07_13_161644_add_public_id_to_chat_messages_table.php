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
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->binary('public_id', length: 16, fixed: true)->nullable()->after('id');
        });

        foreach (DB::table('chat_messages')->orderBy('id')->lazyById() as $chatMessage) {
            DB::table('chat_messages')->where('id', $chatMessage->id)->update([
                'public_id' => BinaryCodec::encode((string) Str::uuid(), 'uuid'),
            ]);
        }

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->binary('public_id', length: 16, fixed: true)->nullable(false)->change();
            $table->unique('public_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
