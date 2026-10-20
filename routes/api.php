<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\FamilyController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChildController;
use App\Http\Controllers\Api\FamilyMemberController;
use App\Http\Controllers\Api\DiaryEntryController;
use App\Http\Controllers\Api\SchoolEventController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\CalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('/families', FamilyController::class)->only(['index', 'show', 'update']);
    Route::post('/families/join', [FamilyController::class, 'join']);

    Route::apiResource('/children', ChildController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('/family-members', FamilyMemberController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/family-members/{familyMember}/photo', [FamilyMemberController::class, 'updatePhoto']);

    Route::post('/children/{child}/photo', [ChildController::class, 'updatePhoto']);

    Route::get('/diary-entries', [DiaryEntryController::class, 'index']);
    Route::post('/diary-entries', [DiaryEntryController::class, 'store']);
    Route::put('/diary-entries/{diaryEntry}', [DiaryEntryController::class, 'update']);
    Route::delete('/diary-entries/{diaryEntry}', [DiaryEntryController::class, 'destroy']);
    Route::post('/diary-entries/{diaryEntry}/media', [MediaController::class, 'storeForDiaryEntry']);

    Route::get('/children/{child}/school-events', [SchoolEventController::class, 'index']);
    Route::post('/children/{child}/school-events', [SchoolEventController::class, 'store']);
    Route::post('/family-members/{familyMember}/school-events', [SchoolEventController::class, 'storeForMember']);
    Route::put('/school-events/{schoolEvent}', [SchoolEventController::class, 'update']);
    Route::delete('/school-events/{schoolEvent}', [SchoolEventController::class, 'destroy']);
    Route::post('/school-events/{schoolEvent}/media', [MediaController::class, 'storeForSchoolEvent']);

    Route::delete('/media/{media}', [MediaController::class, 'destroy']);

    Route::get('/calendar/{year}/{month}', [CalendarController::class, 'monthSummary'])
        ->where(['year' => '[0-9]{4}', 'month' => '[0-9]{1,2}']);
    Route::get('/calendar/day/{date}', [CalendarController::class, 'day'])
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
