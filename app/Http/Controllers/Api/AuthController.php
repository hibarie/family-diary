<?php

namespace App\Http\Controllers\Api;

use App\Models\Family;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'invite_code' => 'nullable|string',
        ]);

        $family = null;

        if (! empty($validated['invite_code'])) {
            $family = Family::where('invite_code', $validated['invite_code'])->first();

            if (! $family) {
                return response()->json(['message' => '招待コードが見つかりません'], 404);
            }
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if ($family) {
            $family->users()->attach($user->id, ['role' => 'member']);
        } else {
            do {
                $inviteCode = Str::upper(Str::random(8));
            } while (Family::where('invite_code', $inviteCode)->exists());

            $family = Family::create([
                'name' => $validated['name'].'の家族',
                'invite_code' => $inviteCode,
            ]);

            $family->users()->attach($user->id, ['role' => 'owner']);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['user' => $user, 'family' => $family, 'token' => $token], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'メールアドレスまたはパスワードが違います'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }
}
