<?php

use App\Models\NotificationSetting;
use Illuminate\Database\Migrations\Migration;
use Modules\MultiPOS\Console\ActivateModuleCommand;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Call the notification setting method from the activate command
        ActivateModuleCommand::addPosMachineRequestNotificationSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove notification settings
        NotificationSetting::where('type', 'pos_machine_request')->delete();
    }
};
