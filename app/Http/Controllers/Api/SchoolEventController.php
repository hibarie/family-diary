<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Child;
use App\Models\FamilyMember;
use App\Models\SchoolEvent;

class SchoolEventController extends Controller
{
    public function index(Request $request, Child $child)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        if ($child->family_id !== $family->id) {
            return response()->json(['message' => 'この子どもの情報にアクセスする権限がありません'], 403);
        }

        return $child->schoolEvents()->with('media')->orderBy('event_date')->get();
    }

    public function store(Request $request, Child $child)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        if ($child->family_id !== $family->id) {
            return response()->json(['message' => 'この子どもの情報にアクセスする権限がありません'], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'event_type' => 'required|in:event,belongings,meeting,other,work,off,holiday,kindergarten,kindergarten_flat,flat,lesson,care',
            'event_date' => 'required|date',
            'memo' => 'nullable|string',
        ]);

        $event = $child->schoolEvents()->create([
            ...$validated,
            'family_id' => $family->id,
        ]);

        return response()->json($event, 201);
    }

    // パパ・ママの予定
    public function storeForMember(Request $request, FamilyMember $familyMember)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        if ($familyMember->family_id !== $family->id) {
            return response()->json(['message' => 'この家族の情報にアクセスする権限がありません'], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'event_type' => 'required|in:event,belongings,meeting,other,work,off,holiday,kindergarten,kindergarten_flat,flat,lesson,care',
            'event_date' => 'required|date',
            'memo' => 'nullable|string',
        ]);

        $event = SchoolEvent::create([
            ...$validated,
            'family_id' => $family->id,
            'family_member_id' => $familyMember->id,
        ]);

        return response()->json($event, 201);
    }

    public function update(Request $request, SchoolEvent $schoolEvent)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        if ($schoolEvent->family_id !== $family->id) {
            return response()->json(['message' => 'この予定を編集する権限がありません'], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'event_type' => 'sometimes|in:event,belongings,meeting,other,work,off,holiday,kindergarten,kindergarten_flat,flat,lesson,care',
            'event_date' => 'sometimes|date',
            'memo' => 'nullable|string',
        ]);

        $schoolEvent->update($validated);

        return response()->json($schoolEvent);
    }

    public function destroy(Request $request, SchoolEvent $schoolEvent)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        if ($schoolEvent->family_id !== $family->id) {
            return response()->json(['message' => 'この予定を削除する権限がありません'], 403);
        }

        $schoolEvent->delete();

        return response()->json(null, 204);
    }
}
