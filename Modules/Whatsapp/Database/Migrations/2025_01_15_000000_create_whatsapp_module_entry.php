<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create WhatsApp module entry in modules table for package selection
        Module::firstOrCreate(
            ['name' => 'Whatsapp'],
            ['name' => 'Whatsapp']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally remove the module entry when rolling back
        // Module::where('name', 'Whatsapp')->delete();
    }
};

