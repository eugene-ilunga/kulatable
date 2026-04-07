<?php

namespace Modules\RestApi\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\DeliveryExecutive;
use Modules\RestApi\Entities\DeliveryPartnerDeviceToken;
use Modules\RestApi\Traits\ApiResponse;

class DeviceController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string|max:255',
            'registration_id' => 'required|string',
            'type' => 'required|string|max:50',
            'details' => 'nullable',
            'delivery_executive_code' => 'required|string|max:255',
        ]);

        $deliveryExecutive = DeliveryExecutive::where('unique_code', $validated['delivery_executive_code'])->first();

        if (! $deliveryExecutive) {
            return $this->errorResponse('Invalid delivery executive code', 404);
        }

        $device = DeliveryPartnerDeviceToken::updateOrCreate(
            [
                'delivery_executive_id' => $deliveryExecutive->id,
                'device_id' => $validated['device_id'],
            ],
            [
                'delivery_executive_code' => $deliveryExecutive->unique_code,
                'fcm_token' => $validated['registration_id'],
                'platform' => $validated['type'], // android/ios
                'status' => 'active',
            ]
        );

        return $this->successResponse([
            'device_id' => $device->device_id,
            'registration_id' => $device->fcm_token,
            'status' => $device->status,
            'type' => $device->platform,
            'delivery_executive_id' => $device->delivery_executive_id,
        ], 'Device registered successfully');
    }

    public function unregister(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string|max:255',
            'delivery_executive_code' => 'required|string|max:255',
        ]);

        $deliveryExecutive = DeliveryExecutive::where('unique_code', $validated['delivery_executive_code'])->first();

        if (! $deliveryExecutive) {
            return $this->errorResponse('Invalid delivery executive code', 404);
        }

        $device = DeliveryPartnerDeviceToken::where('delivery_executive_id', $deliveryExecutive->id)
            ->where('device_id', $validated['device_id'])
            ->first();

        if (! $device) {
            return $this->notFoundResponse('Device not found');
        }

        $device->status = 'inactive';
        $device->save();

        return $this->successResponse([
            'device_id' => $device->device_id,
            'registration_id' => $device->fcm_token,
            'status' => $device->status,
            'type' => $device->platform,
            'delivery_executive_id' => $device->delivery_executive_id,
        ], 'Device unregistered successfully');
    }

}

