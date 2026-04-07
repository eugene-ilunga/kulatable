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
        // Add foreign key for stamp_rule_id if the table exists and column exists
        if (Schema::hasTable('loyalty_stamp_rules') && Schema::hasColumn('kot_items', 'stamp_rule_id')) {
            // Check if foreign key constraint already exists by constraint name or by column
            $constraintName = 'kot_items_stamp_rule_id_foreign';
            $constraintExists = false;
            
            try {
                // First check by constraint name
                $constraints = \Illuminate\Support\Facades\DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'kot_items' 
                    AND CONSTRAINT_NAME = ?
                ", [$constraintName]);
                
                if (!empty($constraints)) {
                    $constraintExists = true;
                } else {
                    // Also check if any foreign key exists on this column (in case constraint name is different)
                    $columnConstraints = \Illuminate\Support\Facades\DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'kot_items' 
                        AND COLUMN_NAME = 'stamp_rule_id'
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ");
                    
                    $constraintExists = !empty($columnConstraints);
                }
            } catch (\Exception $e) {
                // If we can't check, assume it doesn't exist and try to add it
                // The try-catch in the Schema::table will handle if it already exists
            }
            
            if (!$constraintExists) {
                Schema::table('kot_items', function (Blueprint $table) {
                    try {
                        $table->foreign('stamp_rule_id')->references('id')->on('loyalty_stamp_rules')->onDelete('set null');
                    } catch (\Exception $e) {
                        // Foreign key might already exist with different name or structure, ignore
                        // Log the error for debugging but don't fail the migration
                        \Illuminate\Support\Facades\Log::warning('Could not add foreign key for kot_items.stamp_rule_id: ' . $e->getMessage());
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
    }
};
