<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DiaryEntry;

class MediaController extends Controller
{
    public function storeForDiaryEntry(Request $request, DiaryEntry $diaryEntry)
    {
        $user = $request->user();
        $familyId = $user->families()->first()->id;

        if ($diaryEntry->family_id !== $familyId) {
            return response()->json(['message' => 'この日記に添付する権限がありません'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:51200|mimes:jpg,jpeg,png,webp,mp4,mov',
        ]);

        $file = $request->file('file');
        $isVideo = str_starts_with($file->getMimeType(), 'video');

        $path = $file->store('media/' . ($isVideo ? 'videos' : 'images'), 'public');

        $media = $diaryEntry->media()->create([
            'disk' => 'public',
            'path' => $path,
            'type' => $isVideo ? 'video' : 'image',
        ]);

        return response()->json($media, 201);
    }
}
