<?php

namespace Modules\Whatsapp\Console;

use Illuminate\Console\Command;
use App\Models\Module;

class ActivateModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'whatsapp:activate';

    /**
     * The console command description.
     */
    protected $description = 'Activate WhatsApp module and seed template definitions.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Create module entry in modules table if it doesn't exist
            $this->createModuleEntry();

            // Seed WhatsApp template definitions
            $this->seedTemplateDefinitions();
            $this->info('WhatsApp module activated successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to activate WhatsApp module: ' . $e->getMessage());
        }
    }

    /**
     * Create module entry in modules table for package selection
     */
    protected function createModuleEntry(): void
    {
        Module::updateOrCreate(
            ['name' => 'Whatsapp'],
            ['name' => 'Whatsapp']
        );
        $this->info('WhatsApp module entry created in modules table.');
    }

    /**
     * Seed WhatsApp template definitions
     */
    public static function seedTemplateDefinitions(): void
    {
        $seeder = new \Modules\Whatsapp\Database\Seeders\WhatsAppTemplateDefinitionsSeeder();
        $seeder->run();
    }
}
