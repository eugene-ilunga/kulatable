<?php

namespace Modules\Sms\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class SmsUsageLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    const MODULE_NAME = 'sms';
    protected $table = 'sms_usage_logs';

    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'date',
        'gateway',
        'type',
        'count',
        'package_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'count' => 'integer',
    ];

    // Relationships
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Package::class);
    }

    /**
     * Log SMS usage with current datetime
     * This will create separate records for each SMS sent
     */
    public static function logSmsUsage($restaurantId, $branchId, $gateway, $type, $packageId = null)
    {
        $now = now();
        
        // Always create new log entry for each SMS
        self::create([
            'restaurant_id' => $restaurantId,
            'branch_id' => $branchId,
            'date' => $now,
            'gateway' => $gateway,
            'type' => $type,
            'count' => 1,
            'package_id' => $packageId,
        ]);
    }

    /**
     * Alternative method using DB::statement for better performance
     */
    public static function logSmsUsageOptimized($restaurantId, $branchId, $gateway, $type, $packageId = null)
    {
        $now = now();
        
        // Always create new entry
        DB::statement("
            INSERT INTO sms_usage_logs (restaurant_id, branch_id, date, gateway, type, count, package_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())
        ", [$restaurantId, $branchId, $now, $gateway, $type, $packageId]);
    }

    /**
     * Get SMS count for current package assignment (latest package_id for restaurant)
     */
    public static function getCountForCurrentPackage($restaurantId, $gateway = null, $type = null, $startDate = null, $endDate = null)
    {
        // Get the current package_id for the restaurant
        $restaurant = \App\Models\Restaurant::find($restaurantId);
        if (!$restaurant) {
            return 0;
        }

        $currentPackageId = $restaurant->package_id;
        $licenseUpdatedAt = $restaurant->license_updated_at;

        $query = self::where('restaurant_id', $restaurantId)
            ->where('package_id', $currentPackageId);

        if ($gateway) {
            $query->where('gateway', $gateway);
        }

        if ($type) {
            $query->where('type', $type);
        }

        // Only count records after license_updated_at if it exists
        if ($licenseUpdatedAt) {
            $query->where('date', '>=', $licenseUpdatedAt);
        }

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        return $query->sum('count');
    }

    /**
     * Get total SMS count for a restaurant
     */
    public static function getTotalCountForRestaurant($restaurantId, $startDate = null, $endDate = null)
    {
        $query = self::where('restaurant_id', $restaurantId);
        
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        
        return $query->sum('count');
    }

    /**
     * Get SMS count by gateway for a restaurant
     */
    public static function getCountByGatewayForRestaurant($restaurantId, $gateway, $startDate = null, $endDate = null)
    {
        $query = self::where('restaurant_id', $restaurantId)
            ->where('gateway', $gateway);
        
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        
        return $query->sum('count');
    }

    /**
     * Get SMS count by type for a restaurant
     */
    public static function getCountByTypeForRestaurant($restaurantId, $type, $startDate = null, $endDate = null)
    {
        $query = self::where('restaurant_id', $restaurantId)
            ->where('type', $type);
        
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        
        return $query->sum('count');
    }

    /**
     * Get detailed SMS usage statistics
     */
    public static function getDetailedStats($restaurantId, $startDate = null, $endDate = null)
    {
        $query = self::where('restaurant_id', $restaurantId);
        
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        
        return $query->selectRaw('
            gateway,
            type,
            SUM(count) as total_count,
            COUNT(*) as record_count,
            MIN(date) as first_date,
            MAX(date) as last_date
        ')
        ->groupBy('gateway', 'type')
        ->orderBy('gateway')
        ->orderBy('type')
        ->get();
    }

    /**
     * Get daily SMS usage for a restaurant
     */
    public static function getDailyUsage($restaurantId, $startDate = null, $endDate = null)
    {
        $query = self::where('restaurant_id', $restaurantId);
        
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        
        return $query->selectRaw('
            DATE(date) as date,
            gateway,
            type,
            SUM(count) as daily_count
        ')
        ->groupBy('date', 'gateway', 'type')
        ->orderBy('date', 'desc')
        ->orderBy('gateway')
        ->orderBy('type')
        ->get();
    }

    /**
     * Get hourly SMS usage for a restaurant
     */
    public static function getHourlyUsage($restaurantId, $startDate = null, $endDate = null)
    {
        $query = self::where('restaurant_id', $restaurantId);
        
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        
        return $query->selectRaw('
            DATE(date) as date,
            HOUR(date) as hour,
            gateway,
            type,
            SUM(count) as hourly_count
        ')
        ->groupBy('date', 'hour', 'gateway', 'type')
        ->orderBy('date', 'desc')
        ->orderBy('hour', 'desc')
        ->orderBy('gateway')
        ->orderBy('type')
        ->get();
    }

    /**
     * Get SMS usage by specific datetime range
     */
    public static function getUsageByDateTimeRange($restaurantId, $startDateTime, $endDateTime)
    {
        return self::where('restaurant_id', $restaurantId)
            ->whereBetween('date', [$startDateTime, $endDateTime])
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Consolidate existing records by date (useful for migration)
     */
    public static function consolidateByDate()
    {
        // Get all records grouped by restaurant_id, branch_id, package_id, type, gateway, and date
        $records = self::selectRaw('
            restaurant_id,
            branch_id,
            package_id,
            type,
            gateway,
            DATE(date) as date_only,
            SUM(count) as total_count,
            MIN(date) as first_date,
            MAX(date) as last_date
        ')
        ->groupBy('restaurant_id', 'branch_id', 'package_id', 'type', 'gateway', 'date_only')
        ->havingRaw('COUNT(*) > 1') // Only groups with multiple records
        ->get();

        foreach ($records as $record) {
            // Delete all existing records for this combination
            self::where('restaurant_id', $record->restaurant_id)
                ->where('branch_id', $record->branch_id)
                ->where('package_id', $record->package_id)
                ->where('type', $record->type)
                ->where('gateway', $record->gateway)
                ->whereDate('date', $record->date_only)
                ->delete();

            // Create a single consolidated record
            self::create([
                'restaurant_id' => $record->restaurant_id,
                'branch_id' => $record->branch_id,
                'package_id' => $record->package_id,
                'type' => $record->type,
                'gateway' => $record->gateway,
                'date' => $record->first_date,
                'count' => $record->total_count,
            ]);
        }
    }
} 