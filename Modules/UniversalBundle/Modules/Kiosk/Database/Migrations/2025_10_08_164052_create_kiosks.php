<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Branch;
use Modules\Kiosk\Entities\Kiosk;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $checkExists = Branch::withoutGlobalScopes()->get();
        
        if ($checkExists->count() > 0) {
            foreach ($checkExists as $branch) {
                Kiosk::create([
                    'branch_id' => $branch->id,
                    'name' => $branch->name,
                    'code' => (string) random_int(10000, 99999) . $branch->id
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
