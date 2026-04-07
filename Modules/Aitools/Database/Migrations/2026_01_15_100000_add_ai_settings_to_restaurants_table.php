<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Restaurant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('ai_enabled')->default(false)->after('is_active');
            $table->integer('ai_daily_request_limit')->default(50)->after('ai_enabled');
            $table->json('ai_allowed_roles')->nullable()->after('ai_daily_request_limit');
            $table->integer('ai_monthly_tokens_used')->default(0)->after('ai_allowed_roles');
            $table->date('ai_monthly_reset_at')->nullable()->after('ai_monthly_tokens_used');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn([
                'ai_enabled',
                'ai_daily_request_limit',
                'ai_allowed_roles',
                'ai_monthly_tokens_used',
                'ai_monthly_reset_at'
            ]);
        });
    }
};
