<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Whatsapp\Console\ActivateModuleCommand;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Seed WhatsApp template definitions
        ActivateModuleCommand::seedTemplateDefinitions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally remove template definitions on rollback
        \Modules\Whatsapp\Entities\WhatsAppTemplateDefinition::truncate();
    }

};

