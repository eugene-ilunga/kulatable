<?php

namespace Modules\RestApi\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => [__('applicationintegration::messages.invalid_credentials')],
            ]);
        }

        $user = $request->user();

        $token = $user->createToken('application-integration')->plainTextToken;

        $payload = [
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => $this->transformUser($user),
        ];

        // Wrap to match app expectations: status/message/data
        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'status' => true,
            'message' => 'success',
            'data' => $payload,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => $this->transformUser($user),
            'modules' => restaurant_modules(),
        ]);
    }

    protected function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'restaurant_id' => $user->restaurant_id,
            'branch_id' => $user->branch_id,
            'roles' => $user->roles->pluck('name'),
        ];
    }
}

