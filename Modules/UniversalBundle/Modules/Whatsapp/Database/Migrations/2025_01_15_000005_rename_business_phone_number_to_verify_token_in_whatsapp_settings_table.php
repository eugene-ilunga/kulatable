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
        // Check if business_phone_number column exists before renaming
        // This handles cases where the initial migration already created verify_token
        if (Schema::hasColumn('whatsapp_settings', 'business_phone_number')) {
            Schema::table('whatsapp_settings', function (Blueprint $table) {
                $table->renameColumn('business_phone_number', 'verify_token');
            });
            
            Schema::table('whatsapp_settings', function (Blueprint $table) {
                $table->string('verify_token')->nullable()->comment('WhatsApp webhook verify token')->change();
            });
        }
        // If verify_token doesn't exist, create it (for edge cases)
        elseif (!Schema::hasColumn('whatsapp_settings', 'verify_token')) {
            Schema::table('whatsapp_settings', function (Blueprint $table) {
                $table->string('verify_token')->nullable()->comment('WhatsApp webhook verify token')->after('phone_number_id');
            });
        }
        // If verify_token already exists (from initial migration), do nothing
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only reverse if verify_token exists and business_phone_number doesn't
        if (Schema::hasColumn('whatsapp_settings', 'verify_token') && 
            !Schema::hasColumn('whatsapp_settings', 'business_phone_number')) {
            Schema::table('whatsapp_settings', function (Blueprint $table) {
                $table->renameColumn('verify_token', 'business_phone_number');
            });
            
            Schema::table('whatsapp_settings', function (Blueprint $table) {
                $table->string('business_phone_number', 20)->nullable()->comment('Business phone number')->change();
            });
        }
    }
};

