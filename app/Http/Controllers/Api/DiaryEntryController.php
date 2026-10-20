<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DiaryEntry;

class DiaryEntryController extends Controller
{
    public function index(Request $request)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        return DiaryEntry::where('family_id', $family->id)
            ->with('media', 'user')
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
        $family = $user->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        $entry = DiaryEntry::create([
            ...$validated,
            'family_id' => $family->id,
            'user_id' => $user->id,
        ]);

        return response()->json($entry, 201);
    }

    public function update(Request $request, DiaryEntry $diaryEntry)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        if ($diaryEntry->family_id !== $family->id) {
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
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        if ($diaryEntry->family_id !== $family->id) {
            return response()->json(['message' => 'この日記を削除する権限がありません'], 403);
        }

        $diaryEntry->delete();

        return response()->json(null, 204);
    }
}
