<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DiaryEntry;
use App\Models\Media;
use App\Models\SchoolEvent;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function storeForDiaryEntry(Request $request, DiaryEntry $diaryEntry)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        if ($diaryEntry->family_id !== $family->id) {
            return response()->json(['message' => 'この日記に添付する権限がありません'], 403);
        }

        $media = $this->storeUploadedFile($request, $diaryEntry);

        return response()->json($media, 201);
    }

    public function storeForSchoolEvent(Request $request, SchoolEvent $schoolEvent)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        if ($schoolEvent->family_id !== $family->id) {
            return response()->json(['message' => 'この予定に添付する権限がありません'], 403);
        }

        $media = $this->storeUploadedFile($request, $schoolEvent);

        return response()->json($media, 201);
    }

    public function destroy(Request $request, Media $media)
    {
        $family = $request->user()->currentFamily();
        abort_if(! $family, 422, 'まだ家族に参加していません');

        if ($media->mediable->family_id !== $family->id) {
            return response()->json(['message' => 'このメディアを削除する権限がありません'], 403);
        }

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return response()->json(null, 204);
    }

    /**
     * @param  \App\Models\DiaryEntry|\App\Models\SchoolEvent  $mediable
     */
    private function storeUploadedFile(Request $request, $mediable): Media
    {
        $request->validate([
            'file' => 'required|file|max:51200|mimes:jpg,jpeg,png,webp,mp4,mov',
        ]);

        $file = $request->file('file');
        $isVideo = str_starts_with($file->getMimeType(), 'video');

        $path = $file->store('media/' . ($isVideo ? 'videos' : 'images'), 'public');

        return $mediable->media()->create([
            'disk' => 'public',
            'path' => $path,
            'type' => $isVideo ? 'video' : 'image',
        ]);
    }
}
