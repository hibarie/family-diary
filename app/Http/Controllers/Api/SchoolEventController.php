<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Child;

class SchoolEventController extends Controller
{
    public function index(Request $request, Child $child)
    {
        $user = $request->user();
        $familyId = $user->families()->first()->id;

        if ($child->family_id !== $familyId) {
            return response()->json(['message' => 'この子どもの情報にアクセスする権限がありません'], 403);
        }

        return $child->schoolEvents()->orderBy('event_date')->get();
    }

    public function store(Request $request, Child $child)
    {
        $user = $request->user();
        $familyId = $user->families()->first()->id;

        if ($child->family_id !== $familyId) {
            return response()->json(['message' => 'この子どもの情報にアクセスする権限がありません'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'event_type' => 'required|in:event,belongings,meeting,other',
            'event_date' => 'required|date',
            'memo' => 'nullable|string',
        ]);

        $event = $child->schoolEvents()->create([
            ...$validated,
            'family_id' => $familyId,
        ]);

        return response()->json($event, 201);
    }
}
