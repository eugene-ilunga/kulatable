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
        if (!Schema::hasTable('ai_token_usage_history')) {
            Schema::create('ai_token_usage_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('restaurant_id');
                $table->string('month', 7); // Format: YYYY-MM
                $table->integer('tokens_used')->default(0);
                $table->integer('token_limit')->nullable(); // Package limit at that time
                $table->timestamps();

                $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
                $table->unique(['restaurant_id', 'month']);
                $table->index('month');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_token_usage_history');
    }
};
