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
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Bronze, Silver, Gold, etc.
            $table->string('color', 20)->default('#8B7355'); // Hex color for display
            $table->string('icon')->nullable(); // Icon identifier
            $table->integer('min_points')->default(0); // Minimum points required for this tier
            $table->integer('max_points')->nullable(); // Maximum points for this tier (null = unlimited)
            $table->decimal('earning_multiplier', 5, 2)->default(1.00); // Multiplier for earning points (e.g., 1.5 = 50% bonus)
            $table->decimal('redemption_multiplier', 5, 2)->default(1.00); // Multiplier for redemption value (e.g., 1.2 = 20% more value)
            $table->integer('order')->default(0); // Display order (for sorting)
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['restaurant_id', 'is_active']);
            $table->index(['restaurant_id', 'min_points']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_tiers');
    }
};
