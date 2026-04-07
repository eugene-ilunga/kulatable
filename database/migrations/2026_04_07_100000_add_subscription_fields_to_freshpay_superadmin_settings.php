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
            if (!Schema::hasColumn('superadmin_payment_gateways', 'freshpay_api_url')) {
                $table->string('freshpay_api_url')->nullable()->after('freshpay_mode');
            }

            if (!Schema::hasColumn('superadmin_payment_gateways', 'freshpay_firstname')) {
                $table->string('freshpay_firstname')->nullable()->after('freshpay_merchant_secret');
            }

            if (!Schema::hasColumn('superadmin_payment_gateways', 'freshpay_lastname')) {
                $table->string('freshpay_lastname')->nullable()->after('freshpay_firstname');
            }

            if (!Schema::hasColumn('superadmin_payment_gateways', 'freshpay_email')) {
                $table->string('freshpay_email')->nullable()->after('freshpay_lastname');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('superadmin_payment_gateways', function (Blueprint $table) {
            if (Schema::hasColumn('superadmin_payment_gateways', 'freshpay_email')) {
                $table->dropColumn('freshpay_email');
            }

            if (Schema::hasColumn('superadmin_payment_gateways', 'freshpay_lastname')) {
                $table->dropColumn('freshpay_lastname');
            }

            if (Schema::hasColumn('superadmin_payment_gateways', 'freshpay_firstname')) {
                $table->dropColumn('freshpay_firstname');
            }

            if (Schema::hasColumn('superadmin_payment_gateways', 'freshpay_api_url')) {
                $table->dropColumn('freshpay_api_url');
            }
        });
    }
};
