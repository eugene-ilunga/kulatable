<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('webhooks')) {
            Schema::create('webhooks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('restaurant_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('name');
                $table->string('target_url');
                $table->string('secret');
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('max_attempts')->default(3);
                $table->unsignedSmallInteger('backoff_seconds')->default(60);
                $table->json('subscribed_events')->nullable();
                $table->json('source_modules')->nullable();
                $table->json('custom_headers')->nullable();
                $table->boolean('redact_payload')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('webhook_deliveries')) {
            Schema::create('webhook_deliveries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('webhook_id')->index();
                $table->unsignedBigInteger('restaurant_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('event');
                $table->string('status')->default('pending'); // pending|succeeded|failed|disabled
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->unsignedSmallInteger('response_code')->nullable();
                $table->integer('duration_ms')->nullable();
                $table->text('response_body')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('next_retry_at')->nullable();
                $table->string('idempotency_key')->nullable()->index();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->foreign('webhook_id')->references('id')->on('webhooks')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('webhook_signing_keys')) {
            Schema::create('webhook_signing_keys', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('restaurant_id')->nullable()->index();
                $table->string('public_id')->unique();
                $table->string('secret');
                $table->timestamp('rotated_at')->nullable();
                $table->timestamps();
            });
        }

        // Register module + permissions for tenants
        if (Schema::hasTable('modules')) {
            $module = Module::firstOrCreate(['name' => 'Webhooks']);

            if (Schema::hasTable('permissions')) {
                $permissions = [
                    'Manage Webhooks',
                    'View Webhook Logs',
                    'Send Webhook Test',
                ];

                foreach ($permissions as $name) {
                    Permission::firstOrCreate(
                        ['name' => $name, 'guard_name' => 'web'],
                        ['module_id' => $module->id]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('webhook_deliveries')) {
            Schema::dropIfExists('webhook_deliveries');
        }

        if (Schema::hasTable('webhook_signing_keys')) {
            Schema::dropIfExists('webhook_signing_keys');
        }

        if (Schema::hasTable('webhooks')) {
            Schema::dropIfExists('webhooks');
        }

        if (Schema::hasTable('modules')) {
            $module = Module::where('name', 'Webhooks')->first();
            if ($module) {
                if (Schema::hasTable('permissions')) {
                    Permission::where('module_id', $module->id)->delete();
                }
                $module->delete();
            }
        }
    }
};
