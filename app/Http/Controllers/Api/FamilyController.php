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

    public function show(Request $request, Family $family)
    {
        $user = $request->user();

        if (! $family->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'この家族の情報にアクセスする権限がありません'], 403);
        }

        return $family->load(['users', 'children']);
    }

    public function update(Request $request, Family $family)
    {
        $user = $request->user();
        $membership = $family->users()->where('user_id', $user->id)->first();

        if (! $membership) {
            return response()->json(['message' => 'この家族の情報を編集する権限がありません'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $family->update($validated);

        return response()->json($family);
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

        // 参加した家族以外に、自分しかいない家族(登録時に自動で作られたもの)があれば抜けておく。
        // このアプリは1人が1つの家族にだけ属する前提のため。
        $otherFamilies = $user->families()->where('families.id', '!=', $family->id)->get();

        foreach ($otherFamilies as $other) {
            if ($other->users()->count() === 1) {
                $user->families()->detach($other->id);
            }
        }

        return response()->json($family->load('users'));
    }
}
