<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Child;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChildController extends Controller
{
    public function index(Request $request)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        return $family->children()->orderBy('birth_date')->get();
    }

    public function store(Request $request)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $data = [
            'name' => $validated['name'],
            'birth_date' => $validated['birth_date'],
        ];

        if ($request->hasFile('photo')) {
            $data['avatar_path'] = $request->file('photo')->store('children', 'public');
        }

        $child = $family->children()->create($data);

        return response()->json($child, 201);
    }

    public function update(Request $request, Child $child)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        if ($child->family_id !== $family->id) {
            return response()->json(['message' => 'この子どもの情報を編集する権限がありません'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'birth_date' => 'sometimes|date',
        ]);

        $child->update($validated);

        return response()->json($child);
    }

    public function destroy(Request $request, Child $child)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        if ($child->family_id !== $family->id) {
            return response()->json(['message' => 'この子どもの情報を削除する権限がありません'], 403);
        }

        $child->delete();

        return response()->json(null, 204);
    }

    public function updatePhoto(Request $request, Child $child)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        if ($child->family_id !== $family->id) {
            return response()->json(['message' => 'この子どもの写真を変更する権限がありません'], 403);
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        if ($child->avatar_path) {
            Storage::disk('public')->delete($child->avatar_path);
        }

        $child->update([
            'avatar_path' => $request->file('photo')->store('children', 'public'),
        ]);

        return response()->json($child);
    }
}
