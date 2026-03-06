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
        Schema::table('superadmin_payment_gateways', function (Blueprint $table) {
            if (!Schema::hasColumn('superadmin_payment_gateways', 'freshpay_status')) {
                $table->boolean('freshpay_status')->default(false);
            }

            if (!Schema::hasColumn('superadmin_payment_gateways', 'freshpay_mode')) {
                $table->enum('freshpay_mode', ['test', 'live'])->default('test');
            }

            if (!Schema::hasColumn('superadmin_payment_gateways', 'freshpay_merchant_id')) {
                $table->string('freshpay_merchant_id')->nullable();
            }

            if (!Schema::hasColumn('superadmin_payment_gateways', 'freshpay_merchant_secret')) {
                $table->text('freshpay_merchant_secret')->nullable();
            }

            if (!Schema::hasColumn('superadmin_payment_gateways', 'freshpay_method')) {
                $table->string('freshpay_method')->nullable();
            }

            if (!Schema::hasColumn('superadmin_payment_gateways', 'freshpay_callback_secret_key')) {
                $table->text('freshpay_callback_secret_key')->nullable();
            }

            if (!Schema::hasColumn('superadmin_payment_gateways', 'freshpay_callback_hmac_key')) {
                $table->text('freshpay_callback_hmac_key')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('superadmin_payment_gateways', function (Blueprint $table) {
            if (Schema::hasColumn('superadmin_payment_gateways', 'freshpay_status')) {
                $table->dropColumn('freshpay_status');
            }

            if (Schema::hasColumn('superadmin_payment_gateways', 'freshpay_mode')) {
                $table->dropColumn('freshpay_mode');
            }

            if (Schema::hasColumn('superadmin_payment_gateways', 'freshpay_merchant_id')) {
                $table->dropColumn('freshpay_merchant_id');
            }

            if (Schema::hasColumn('superadmin_payment_gateways', 'freshpay_merchant_secret')) {
                $table->dropColumn('freshpay_merchant_secret');
            }

            if (Schema::hasColumn('superadmin_payment_gateways', 'freshpay_method')) {
                $table->dropColumn('freshpay_method');
            }

            if (Schema::hasColumn('superadmin_payment_gateways', 'freshpay_callback_secret_key')) {
                $table->dropColumn('freshpay_callback_secret_key');
            }

            if (Schema::hasColumn('superadmin_payment_gateways', 'freshpay_callback_hmac_key')) {
                $table->dropColumn('freshpay_callback_hmac_key');
            }
        });
    }
};

