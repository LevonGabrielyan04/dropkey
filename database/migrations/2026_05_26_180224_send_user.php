<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('send_user', function (Blueprint $table) {
            $table->binary('send_id', length: 16, fixed: true);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->foreign('send_id')->references('id')->on('sends')->cascadeOnDelete();
            $table->primary(['send_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('send_user');
    }
};
