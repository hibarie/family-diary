<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DiaryEntry;

class DiaryEntryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $familyId = $user->families()->first()->id;

        return DiaryEntry::where('family_id', $familyId)
            ->orderByDesc('entry_date')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'body' => 'required|string',
            'entry_date' => 'required|date',
        ]);

        $user = $request->user();
        $familyId = $user->families()->first()->id;

        $entry = DiaryEntry::create([
            ...$validated,
            'family_id' => $familyId,
            'user_id' => $user->id,
        ]);

        return response()->json($entry, 201);
    }

    public function update(Request $request, DiaryEntry $diaryEntry)
    {
        $user = $request->user();
        $familyId = $user->families()->first()->id;

        if ($diaryEntry->family_id !== $familyId) {
            return response()->json(['message' => 'この日記を編集する権限がありません'], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'body' => 'sometimes|string',
            'entry_date' => 'sometimes|date',
        ]);

        $diaryEntry->update($validated);

        return response()->json($diaryEntry);
    }

    public function destroy(Request $request, DiaryEntry $diaryEntry)
    {
        $user = $request->user();
        $familyId = $user->families()->first()->id;

        if ($diaryEntry->family_id !== $familyId) {
            return response()->json(['message' => 'この日記を削除する権限がありません'], 403);
        }

        $diaryEntry->delete();

        return response()->json(null, 204);
    }
}
