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
        Schema::table('restaurant_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurant_payments', 'freshpay_customer_number')) {
                $table->string('freshpay_customer_number')->nullable()->after('reference_id');
            }

            if (!Schema::hasColumn('restaurant_payments', 'freshpay_method')) {
                $table->string('freshpay_method')->nullable()->after('freshpay_customer_number');
            }

            if (!Schema::hasColumn('restaurant_payments', 'freshpay_request_payload')) {
                $table->json('freshpay_request_payload')->nullable()->after('freshpay_method');
            }

            if (!Schema::hasColumn('restaurant_payments', 'freshpay_response_payload')) {
                $table->json('freshpay_response_payload')->nullable()->after('freshpay_request_payload');
            }

            if (!Schema::hasColumn('restaurant_payments', 'freshpay_callback_payload')) {
                $table->json('freshpay_callback_payload')->nullable()->after('freshpay_response_payload');
            }

            if (!Schema::hasColumn('restaurant_payments', 'freshpay_status_description')) {
                $table->text('freshpay_status_description')->nullable()->after('freshpay_callback_payload');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurant_payments', function (Blueprint $table) {
            if (Schema::hasColumn('restaurant_payments', 'freshpay_status_description')) {
                $table->dropColumn('freshpay_status_description');
            }

            if (Schema::hasColumn('restaurant_payments', 'freshpay_callback_payload')) {
                $table->dropColumn('freshpay_callback_payload');
            }

            if (Schema::hasColumn('restaurant_payments', 'freshpay_response_payload')) {
                $table->dropColumn('freshpay_response_payload');
            }

            if (Schema::hasColumn('restaurant_payments', 'freshpay_request_payload')) {
                $table->dropColumn('freshpay_request_payload');
            }

            if (Schema::hasColumn('restaurant_payments', 'freshpay_method')) {
                $table->dropColumn('freshpay_method');
            }

            if (Schema::hasColumn('restaurant_payments', 'freshpay_customer_number')) {
                $table->dropColumn('freshpay_customer_number');
            }
        });
    }
};
