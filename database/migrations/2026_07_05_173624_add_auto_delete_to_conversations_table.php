<?php

use App\Enums\TimePeriod;
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
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('auto_delete', 30)
                ->default(TimePeriod::SEVEN_DAYS->value)
                ->after('user_two_id');

            $table->index('auto_delete');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['auto_delete']);
            $table->dropColumn('auto_delete');
        });
    }
};
