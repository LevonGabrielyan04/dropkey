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
        Schema::table('user_identity_keys', function (Blueprint $table) {
            $table->binary('browser_db_id', length: 16, fixed: true)
                ->unique()
                ->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_identity_keys', function (Blueprint $table) {
            $table->dropUnique(['browser_db_id']);
            $table->dropColumn('browser_db_id');
        });
    }
};
