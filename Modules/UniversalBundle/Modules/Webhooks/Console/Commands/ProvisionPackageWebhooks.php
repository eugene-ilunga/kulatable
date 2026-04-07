<?php

namespace Modules\Webhooks\Console\Commands;

use App\Models\Restaurant;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Modules\Webhooks\Entities\Webhook;

class ProvisionPackageWebhooks extends Command
{
    protected $signature = 'webhooks:provision {--restaurant_id=}';
    protected $description = 'Provision webhooks for restaurants based on package defaults';

    public function handle(): int
    {
        $query = Restaurant::query();
        if ($this->option('restaurant_id')) {
            $query->where('id', $this->option('restaurant_id'));
        }

        $restaurants = $query->get();
        $count = 0;

        foreach ($restaurants as $restaurant) {
            $packageId = $restaurant->package_id;
            if (! $packageId) {
                continue;
            }

            $defaults = DB::table('webhook_package_defaults')->where('package_id', $packageId)->first();
            if (! $defaults || ! $defaults->auto_provision) {
                continue;
            }

            $exists = Webhook::where('restaurant_id', $restaurant->id)->exists();
            if ($exists) {
                continue;
            }

            $secret = $defaults->default_secret ?: Str::random(32);
            $target = $defaults->default_target_url ?: 'https://example.com/webhooks'; // placeholder

            Webhook::create([
                'restaurant_id' => $restaurant->id,
                'branch_id' => null,
                'name' => 'Default Webhook',
                'target_url' => $target,
                'secret' => $secret,
                'is_active' => true,
                'max_attempts' => 3,
                'backoff_seconds' => 60,
                'subscribed_events' => $defaults->allowed_events ? json_decode($defaults->allowed_events, true) : null,
                'source_modules' => null,
                'custom_headers' => null,
                'provisioned_by' => 'system',
            ]);

            $count++;
        }

        $this->info("Provisioned {$count} webhook(s).");

        return self::SUCCESS;
    }
}
