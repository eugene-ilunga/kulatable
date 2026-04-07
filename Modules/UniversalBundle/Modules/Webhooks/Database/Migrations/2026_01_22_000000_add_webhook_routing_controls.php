<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('webhook_event_catalog')) {
            Schema::create('webhook_event_catalog', function (Blueprint $table) {
                $table->id();
                $table->string('event_key');
                $table->string('module');
                $table->string('description')->nullable();
                $table->unsignedSmallInteger('schema_version')->default(1);
                $table->json('redact_defaults')->nullable();
                $table->boolean('is_notification')->default(false);
                $table->boolean('enabled_global')->default(true);
                $table->timestamps();
                $table->unique(['event_key', 'module']);
            });
        }

        if (! Schema::hasTable('webhook_global_policies')) {
            Schema::create('webhook_global_policies', function (Blueprint $table) {
                $table->id();
                $table->string('event_key');
                $table->string('module');
                $table->boolean('allowed')->default(true);
                $table->json('allowed_packages')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['event_key', 'module']);
            });
        }

        if (! Schema::hasTable('webhook_package_defaults')) {
            Schema::create('webhook_package_defaults', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('package_id')->unique();
                $table->json('allowed_events')->nullable();
                $table->boolean('auto_provision')->default(true);
                $table->string('default_target_url')->nullable();
                $table->string('default_secret')->nullable();
                $table->unsignedSmallInteger('rotate_interval_days')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('webhooks')) {
            Schema::table('webhooks', function (Blueprint $table) {
                if (! Schema::hasColumn('webhooks', 'provisioned_by')) {
                    $table->string('provisioned_by')->nullable()->after('custom_headers');
                }
                if (! Schema::hasColumn('webhooks', 'last_rotated_at')) {
                    $table->timestamp('last_rotated_at')->nullable()->after('provisioned_by');
                }
                if (! Schema::hasColumn('webhooks', 'signature_version')) {
                    $table->unsignedTinyInteger('signature_version')->default(1)->after('last_rotated_at');
                }
            });
        }

        if (Schema::hasTable('webhook_event_catalog')) {
            $now = now();
            $events = [
                ['event_key' => 'order.created', 'module' => 'Order', 'description' => 'Order created', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => false, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
                ['event_key' => 'order.updated', 'module' => 'Order', 'description' => 'Order updated', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => false, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
                ['event_key' => 'order.paid', 'module' => 'Order', 'description' => 'Order paid', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => false, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
                ['event_key' => 'order.bill_sent', 'module' => 'Order', 'description' => 'Bill sent', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => false, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
                ['event_key' => 'reservation.received', 'module' => 'Reservation', 'description' => 'Reservation received', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => false, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
                ['event_key' => 'reservation.confirmed', 'module' => 'Reservation', 'description' => 'Reservation confirmed', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => false, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
                ['event_key' => 'restaurant.created', 'module' => 'Onboarding', 'description' => 'Restaurant created', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => false, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
                ['event_key' => 'kot.updated', 'module' => 'Kitchen', 'description' => 'KOT updated', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => false, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
                ['event_key' => 'printjob.created', 'module' => 'Kitchen', 'description' => 'Print job created', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => false, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
                ['event_key' => 'inventory.stock_updated', 'module' => 'Inventory', 'description' => 'Inventory stock updated', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => false, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
                ['event_key' => 'payment.success', 'module' => 'Payment', 'description' => 'Payment success', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => false, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
                ['event_key' => 'payment.failed', 'module' => 'Payment', 'description' => 'Payment failed', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => false, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
                ['event_key' => 'webhooks.test', 'module' => 'Webhooks', 'description' => 'Test webhook', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => false, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
                ['event_key' => 'notification.created', 'module' => 'Notification', 'description' => 'Notification created', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => true, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
                ['event_key' => 'notification.read', 'module' => 'Notification', 'description' => 'Notification read', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => true, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
                ['event_key' => 'message.received', 'module' => 'Notification', 'description' => 'Message received', 'schema_version' => 1, 'redact_defaults' => json_encode([]), 'is_notification' => true, 'enabled_global' => true, 'created_at' => $now, 'updated_at' => $now],
            ];

            DB::table('webhook_event_catalog')->upsert(
                $events,
                ['event_key', 'module'],
                ['description', 'schema_version', 'redact_defaults', 'is_notification', 'enabled_global', 'updated_at']
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('webhook_package_defaults')) {
            Schema::dropIfExists('webhook_package_defaults');
        }

        if (Schema::hasTable('webhook_global_policies')) {
            Schema::dropIfExists('webhook_global_policies');
        }

        if (Schema::hasTable('webhook_event_catalog')) {
            Schema::dropIfExists('webhook_event_catalog');
        }

        if (Schema::hasTable('webhooks')) {
            Schema::table('webhooks', function (Blueprint $table) {
                if (Schema::hasColumn('webhooks', 'provisioned_by')) {
                    $table->dropColumn('provisioned_by');
                }
                if (Schema::hasColumn('webhooks', 'last_rotated_at')) {
                    $table->dropColumn('last_rotated_at');
                }
                if (Schema::hasColumn('webhooks', 'signature_version')) {
                    $table->dropColumn('signature_version');
                }
            });
        }
    }
};
