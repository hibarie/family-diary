<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FamilyMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FamilyMemberController extends Controller
{
    public function index(Request $request)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        return $family->members()->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:20',
            'birth_date' => 'required|date',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $data = [
            'name' => $validated['name'],
            'role' => $validated['role'],
            'birth_date' => $validated['birth_date'],
        ];

        if ($request->hasFile('photo')) {
            $data['avatar_path'] = $request->file('photo')->store('members', 'public');
        }

        return response()->json($family->members()->create($data), 201);
    }

    public function update(Request $request, FamilyMember $familyMember)
    {
        $this->authorizeMember($request, $familyMember);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'birth_date' => 'sometimes|date',
        ]);

        $familyMember->update($validated);

        return response()->json($familyMember);
    }

    public function updatePhoto(Request $request, FamilyMember $familyMember)
    {
        $this->authorizeMember($request, $familyMember);

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        if ($familyMember->avatar_path) {
            Storage::disk('public')->delete($familyMember->avatar_path);
        }

        $familyMember->update([
            'avatar_path' => $request->file('photo')->store('members', 'public'),
        ]);

        return response()->json($familyMember);
    }

    public function destroy(Request $request, FamilyMember $familyMember)
    {
        $this->authorizeMember($request, $familyMember);

        $familyMember->delete();

        return response()->json(null, 204);
    }

    private function authorizeMember(Request $request, FamilyMember $familyMember): void
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');
        abort_unless($familyMember->family_id === $family->id, 403, 'この家族情報にアクセスする権限がありません');
    }
}
