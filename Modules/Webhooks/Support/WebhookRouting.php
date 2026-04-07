<?php

namespace Modules\Webhooks\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WebhookRouting
{
    /** @var array<string, bool> */
    private static array $hasTableCache = [];

    /** @var array<string, bool> packaged allows() results: packageId|module|eventKey */
    private static array $allowsCache = [];

    /** @var array<string, object|null> eventKey + "\0" + module => global policy row */
    private static array $globalPolicyRowCache = [];

    /** @var array<int, object|null> package_id => defaults row */
    private static array $packageDefaultsRowCache = [];

    /**
     * Schema::hasTable() hits information_schema on every call; webhooks may run it many times per request/job.
     */
    private static function hasTableCached(string $table): bool
    {
        if (! array_key_exists($table, self::$hasTableCache)) {
            self::$hasTableCache[$table] = Schema::hasTable($table);
        }

        return self::$hasTableCache[$table];
    }

    /**
     * For tests or rare migrations mid-process. Optional; workers restart clears cache.
     */
    public static function clearRoutingCache(): void
    {
        self::$hasTableCache = [];
        self::$allowsCache = [];
        self::$globalPolicyRowCache = [];
        self::$packageDefaultsRowCache = [];
    }

    public static function clearSchemaCache(): void
    {
        self::clearRoutingCache();
    }

    /**
     * Decide if an event is allowed to dispatch for the given tenant/package.
     * Defaults to allow when no policy/default rows exist (backward compatible).
     */
    public static function allows(?int $restaurantId, ?int $branchId, ?int $packageId, string $module, string $eventKey): bool
    {
        $cacheKey = ($packageId ?? 0) . '|' . $module . '|' . $eventKey;
        if (array_key_exists($cacheKey, self::$allowsCache)) {
            return self::$allowsCache[$cacheKey];
        }

        $result = self::computeAllows($packageId, $module, $eventKey);
        self::$allowsCache[$cacheKey] = $result;

        return $result;
    }

    private static function globalPolicyRow(string $eventKey, string $module): ?object
    {
        $pk = $eventKey . "\0" . $module;
        if (! array_key_exists($pk, self::$globalPolicyRowCache)) {
            self::$globalPolicyRowCache[$pk] = DB::table('webhook_global_policies')
                ->where('event_key', $eventKey)
                ->where('module', $module)
                ->first();
        }

        return self::$globalPolicyRowCache[$pk];
    }

    private static function packageDefaultsRow(int $packageId): ?object
    {
        if (! array_key_exists($packageId, self::$packageDefaultsRowCache)) {
            self::$packageDefaultsRowCache[$packageId] = DB::table('webhook_package_defaults')
                ->where('package_id', $packageId)
                ->first();
        }

        return self::$packageDefaultsRowCache[$packageId];
    }

    private static function computeAllows(?int $packageId, string $module, string $eventKey): bool
    {
        if (self::hasTableCached('webhook_global_policies')) {
            $policy = self::globalPolicyRow($eventKey, $module);

            if ($policy) {
                if (! (bool) $policy->allowed) {
                    return false;
                }

                if ($packageId && $policy->allowed_packages !== null) {
                    $allowedPackages = json_decode($policy->allowed_packages, true) ?: [];
                    if (! empty($allowedPackages) && ! in_array($packageId, $allowedPackages, true)) {
                        return false;
                    }
                }
            }
        }

        if ($packageId && self::hasTableCached('webhook_package_defaults')) {
            $defaults = self::packageDefaultsRow($packageId);

            if ($defaults && $defaults->allowed_events !== null) {
                $allowedEvents = json_decode($defaults->allowed_events, true) ?: [];
                if (! empty($allowedEvents) && ! in_array($eventKey, $allowedEvents, true)) {
                    return false;
                }
            }
        }

        return true;
    }
}
