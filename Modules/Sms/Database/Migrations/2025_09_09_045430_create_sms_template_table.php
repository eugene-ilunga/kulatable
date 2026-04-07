<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->string('flow_id')->nullable();
            $table->timestamps();
        });

        // Insert default SMS templates
        $smsTemplates = [
            [
                'type' => 'reservation_confirmed',
                'flow_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'order_bill_sent',
                'flow_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'send_otp',
                'flow_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'send_verify_otp',
                'flow_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('sms_templates')->insert($smsTemplates);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};
