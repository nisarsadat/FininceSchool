<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string'],
        ]);

        $code = strtoupper(trim($data['code']));
        $user = User::query()->where('code', $code)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'code' => 'That code or password is not correct.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'code' => 'This account is turned off. Ask an administrator.',
            ]);
        }

        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->accessPayload(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->accessPayload());
    }
}
