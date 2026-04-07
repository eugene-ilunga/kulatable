<?php

namespace Modules\RestApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Printer;
use App\Models\ReceiptSetting;
use App\Models\Restaurant;
use App\Models\PaymentGatewayCredential;
use Illuminate\Http\Request;

class PlatformController extends Controller
{
    protected $restaurant;
    protected $branch;
    protected $user;

    public function __construct()
    {
        $this->user = auth()->user();

        if ($this->user && $this->user->restaurant_id) {
            $this->restaurant = Restaurant::with('currency', 'branches')->find($this->user->restaurant_id);
            $this->branch = $this->user->branch_id
                ? Branch::find($this->user->branch_id)
                : ($this->restaurant ? $this->restaurant->branches->first() : null);
        }

        if (! $this->restaurant) {
            $this->restaurant = Restaurant::with('currency', 'branches')->first();
        }

        if (! $this->branch && $this->restaurant) {
            $this->branch = $this->restaurant->branches()->first();
        }
    }

    public function config()
    {
        $restaurant = $this->restaurant;
        $branch = $this->branch;

        $gateway = $restaurant ? PaymentGatewayCredential::where('restaurant_id', $restaurant->id)->first() : null;
        $gatewayMeta = $gateway
            ? collect($gateway->getAttributes())
            ->filter(function ($value, $key) {
                return str_contains($key, 'status') || str_contains($key, 'mode') || in_array($key, ['currency', 'qr_code_image', 'qr_code_image_url']);
            })
            ->merge([
                'qr_code_image_url' => $gateway->qr_code_image_url ?? null,
            ])
            ->all()
            : [];

        return response()->json([
            'user' => $this->compactUser(),
            'restaurant' => $restaurant ? [
                'id' => $restaurant->id,
                'name' => $restaurant->restaurant_name,
                'hash' => $restaurant->hash,
                'logo_url' => $restaurant->logoUrl,
                'country_id' => $restaurant->country_id,
                'currency' => $restaurant->currency ? [
                    'id' => $restaurant->currency->id,
                    'name' => $restaurant->currency->currency_name,
                    'symbol' => $restaurant->currency->currency_symbol,
                    'code' => $restaurant->currency->currency_code,
                    'exchange_rate' => $restaurant->currency->exchange_rate,
                ] : null,
            ] : null,
            'branch' => $branch ? [
                'id' => $branch->id,
                'name' => $branch->name ?? $branch->branch_name ?? null,
                'hash' => $branch->hash ?? $branch->unique_hash ?? null,
                'address' => $branch->address,
                'timezone' => $branch->timezone ?? global_setting()->timezone,
            ] : null,
            'features' => $this->featureFlags(),
            'modules' => restaurant_modules(),
            'payment_gateways' => $gatewayMeta,
            'languages' => \App\Models\LanguageSetting::where('active', 1)->get(['language_name', 'language_code']),
        ]);
    }

    public function permissions()
    {
        $user = $this->user;
        if (! $user) {
            return response()->json([]);
        }

        return response()->json([
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function printers()
    {
        if (! $this->branch) {
            return response()->json([]);
        }

        $printers = Printer::where('branch_id', $this->branch->id)->get();

        return response()->json($printers);
    }

    public function receiptSettings()
    {
        if (! $this->restaurant) {
            return response()->json([]);
        }

        return response()->json(
            ReceiptSetting::where('restaurant_id', $this->restaurant->id)->first()
        );
    }

    public function switchBranch(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        if (! $this->user) {
            return response()->json(['message' => __('applicationintegration::messages.unauthorized')], 401);
        }

        $this->user->branch_id = $data['branch_id'];
        $this->user->saveQuietly();

        $this->branch = Branch::find($data['branch_id']);

        return response()->json([
            'success' => true,
            'branch' => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name ?? $this->branch->branch_name ?? null,
            ] : null,
        ]);
    }

    protected function featureFlags(): array
    {
        $modules = restaurant_modules();

        return [
            'pos' => in_array('POS', $modules),
            'order' => in_array('Order', $modules),
            'delivery' => in_array('Delivery', $modules) || in_array('Delivery Executive', $modules),
            'customer_app' => in_array('Customer', $modules) || in_array('Customer App', $modules),
            'waiter_app' => in_array('Waiter App', $modules) || in_array('Waiter', $modules),
            'theme' => in_array('Theme Setting', $modules),
            'payment_gateway_integration' => in_array('Payment Gateway Integration', $modules),
        ];
    }

    protected function compactUser(): ?array
    {
        if (! $this->user) {
            return null;
        }

        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'restaurant_id' => $this->user->restaurant_id,
            'branch_id' => $this->user->branch_id,
            'roles' => $this->user->roles->pluck('name'),
        ];
    }
}
