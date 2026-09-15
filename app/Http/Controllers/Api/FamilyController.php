<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Family;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->families()->with('users')->get();
    }

    public function join(Request $request)
    {
        $validated = $request->validate([
            'invite_code' => 'required|string',
        ]);

        $family = Family::where('invite_code', $validated['invite_code'])->first();

        if (! $family) {
            return response()->json(['message' => '招待コードが見つかりません'], 404);
        }

        $user = $request->user();

        if ($family->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'すでにこの家族に参加しています'], 409);
        }

        $family->users()->attach($user->id, ['role' => 'member']);

        return response()->json($family->load('users'));
    }
}
