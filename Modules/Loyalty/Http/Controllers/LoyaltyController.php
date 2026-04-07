<?php

namespace Modules\Loyalty\Http\Controllers;

use App\Models\Restaurant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Loyalty\Entities\LoyaltySetting;

class LoyaltyController extends Controller
{
    public function customerAccount(string $hash)
    {
        $restaurant = Restaurant::with(['currency', 'branches'])
            ->where('hash', $hash)
            ->firstOrFail();

        return $this->resolveLoyaltyAccount($restaurant);
    }

    public function customerAccountSubdomain()
    {
        $restaurant = Restaurant::with(['currency', 'branches'])
            ->where('sub_domain', request()->getHost())
            ->firstOrFail();

        return $this->resolveLoyaltyAccount($restaurant);
    }

    private function resolveLoyaltyAccount(Restaurant $restaurant)
    {
        $loyaltySettings = LoyaltySetting::getForRestaurant($restaurant->id);
        if (!$loyaltySettings || !$loyaltySettings->isEnabled()) {
            abort(404);
        }

        $shopBranch = null;
        if (request()->filled('branch')) {
            $branchParam = request('branch');
            $shopBranch = $restaurant->branches->first(function ($branch) use ($branchParam) {
                return (string) $branch->unique_hash === (string) $branchParam
                    || (string) $branch->id === (string) $branchParam;
            });
        }

        if (!$shopBranch) {
            $shopBranch = $restaurant->branches->first();
        }

        return view('loyalty::shop.customer-loyalty-account', compact('restaurant', 'shopBranch'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('loyalty::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('loyalty::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('loyalty::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('loyalty::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
