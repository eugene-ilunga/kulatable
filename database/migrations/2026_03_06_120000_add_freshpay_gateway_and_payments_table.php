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
        if (!Schema::hasColumn('payment_gateway_credentials', 'freshpay_status')) {
            Schema::table('payment_gateway_credentials', function (Blueprint $table) {
                $table->boolean('freshpay_status')->default(false);
                $table->enum('freshpay_mode', ['test', 'live'])->default('test');
                $table->string('freshpay_merchant_id')->nullable();
                $table->text('freshpay_merchant_secret')->nullable();
                $table->string('freshpay_method')->nullable();
                $table->text('freshpay_callback_secret_key')->nullable();
                $table->text('freshpay_callback_hmac_key')->nullable();
            });
        }

        if (!Schema::hasTable('freshpay_payments')) {
            Schema::create('freshpay_payments', function (Blueprint $table) {
                $table->id();
                $table->string('freshpay_payment_id')->nullable();
                $table->string('freshpay_reference')->nullable();
                $table->string('freshpay_action')->nullable();
                $table->string('freshpay_method')->nullable();
                $table->string('customer_number')->nullable();
                $table->string('financial_institution_id')->nullable();
                $table->string('trans_status')->nullable();
                $table->text('trans_status_description')->nullable();

                $table->unsignedBigInteger('order_id');
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

                $table->decimal('amount', 10, 2);
                $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending');
                $table->timestamp('payment_date')->nullable();
                $table->json('payment_error_response')->nullable();
                $table->json('callback_payload')->nullable();
                $table->timestamps();

                $table->index('freshpay_reference');
                $table->index('freshpay_payment_id');
            });
        }

        if (!Schema::hasColumn('global_settings', 'enable_freshpay')) {
            Schema::table('global_settings', function (Blueprint $table) {
                $table->boolean('enable_freshpay')->default(true);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('payment_gateway_credentials', 'freshpay_status')) {
            Schema::table('payment_gateway_credentials', function (Blueprint $table) {
                $table->dropColumn([
                    'freshpay_status',
                    'freshpay_mode',
                    'freshpay_merchant_id',
                    'freshpay_merchant_secret',
                    'freshpay_method',
                    'freshpay_callback_secret_key',
                    'freshpay_callback_hmac_key',
                ]);
            });
        }

        Schema::dropIfExists('freshpay_payments');

        if (Schema::hasColumn('global_settings', 'enable_freshpay')) {
            Schema::table('global_settings', function (Blueprint $table) {
                $table->dropColumn('enable_freshpay');
            });
        }
    }
};

