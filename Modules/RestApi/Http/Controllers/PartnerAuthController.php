<?php

namespace Modules\RestApi\Http\Controllers;

use App\Models\DeliveryExecutive;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestApi\Traits\ApiResponse;

class PartnerAuthController extends Controller
{
    use ApiResponse;

    /**
     * Partner login
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'unique_code' => 'required|string',
        ]);

        $partner = DeliveryExecutive::where('unique_code', $request->unique_code)->first();

        if (!$partner) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        if ($partner->status === 'inactive') {
            return $this->errorResponse('Partner account is inactive', 403);
        }

        return $this->successResponse([
            'partner' => $this->transformPartner($partner),
            'unique_code' => $partner->unique_code,
        ], 'Login successful');
    }

    /**
     * Partner logout
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Since we're using unique code in headers, logout is mainly client-side
        // But we can log the activity if needed

        return $this->successResponse(null, 'Logout successful');
    }

    /**
     * Transform partner data for response
     *
     * @param DeliveryExecutive $partner
     * @return array
     */
    protected function transformPartner(DeliveryExecutive $partner): array
    {
        return [
            'id' => $partner->id,
            'name' => $partner->name,
            'phone' => $partner->phone,
            'photo' => $partner->photo,
            'status' => $partner->status,
            'branch_id' => $partner->branch_id,
        ];
    }

}
