<?php

namespace Modules\RestApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Currency;
use App\Models\CustomerAddress;
use App\Models\LanguageSetting;
use App\Models\PaymentGatewayCredential;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class AppWideController extends Controller
{
    protected $restaurantId;
    protected $branchId;

    public function __construct()
    {
        $user = auth()->user();
        $this->restaurantId = $user?->restaurant_id;
        $this->branchId = $user?->branch_id;
    }

    public function languages()
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('language_settings')) {
                $columns = \Illuminate\Support\Facades\Schema::getColumnListing('language_settings');
                $allowed = collect(['language_name', 'language_code', 'status', 'active'])
                    ->filter(fn($col) => in_array($col, $columns))
                    ->values()
                    ->all();

                if (! empty($allowed)) {
                    return response()->json(LanguageSetting::all($allowed));
                }
            }
        } catch (\Throwable $e) {
            // fall through to defaults
        }

        return response()->json(LanguageSetting::LANGUAGES);
    }

    public function currencies()
    {
        return response()->json(Currency::all(['id', 'currency_name', 'currency_symbol', 'currency_code', 'exchange_rate']));
    }

    public function paymentGateways()
    {
        if (! $this->restaurantId) {
            return response()->json([]);
        }

        $gateways = PaymentGatewayCredential::where('restaurant_id', $this->restaurantId)->first();

        return response()->json($gateways);
    }

    public function staff()
    {
        if (! $this->restaurantId) {
            return response()->json([]);
        }

        return response()->json(
            User::where('restaurant_id', $this->restaurantId)
                ->select('id', 'name', 'email', 'branch_id')
                ->get()
        );
    }

    public function roles()
    {
        if (! $this->restaurantId) {
            return response()->json([]);
        }

        return response()->json(
            Role::where('name', 'like', '%_' . $this->restaurantId)->pluck('name')
        );
    }

    public function areas()
    {
        if (! $this->branchId) {
            return response()->json([]);
        }

        return response()->json(
            Area::where('branch_id', $this->branchId)->get()
        );
    }

    public function customerAddresses(Request $request)
    {
        $customerId = $request->query('customer_id');
        if (! $customerId) {
            return response()->json([]);
        }

        return response()->json(
            CustomerAddress::where('customer_id', $customerId)->get()
        );
    }

    public function storeCustomerAddress(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|integer',
            'address' => 'required|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $address = CustomerAddress::create($data);

        return response()->json($address);
    }

    public function updateCustomerAddress(Request $request, $id)
    {
        $data = $request->validate([
            'address' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $address = CustomerAddress::findOrFail($id);
        $address->update($data);

        return response()->json($address);
    }

    public function deleteCustomerAddress($id)
    {
        $address = CustomerAddress::findOrFail($id);
        $address->delete();

        return response()->json(['success' => true]);
    }
}
