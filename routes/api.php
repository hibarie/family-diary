<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\FamilyController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DiaryEntryController;
use App\Http\Controllers\Api\SchoolEventController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\CalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/families', FamilyController::class);
    Route::get('/diary-entries', [DiaryEntryController::class, 'index']);
    Route::post('/diary-entries', [DiaryEntryController::class, 'store']);
    Route::put('/diary-entries/{diaryEntry}', [DiaryEntryController::class, 'update']);
    Route::delete('/diary-entries/{diaryEntry}', [DiaryEntryController::class, 'destroy']);
    Route::post('/diary-entries/{diaryEntry}/media', [MediaController::class, 'storeForDiaryEntry']);
    Route::get('/children/{child}/school-events', [SchoolEventController::class, 'index']);
    Route::post('/children/{child}/school-events', [SchoolEventController::class, 'store']);
    Route::get('/calendar/{year}/{month}', [CalendarController::class, 'monthSummary'])
        ->where(['year' => '[0-9]{4}', 'month' => '[0-9]{1,2}']);
    Route::get('/calendar/day/{date}', [CalendarController::class, 'day'])
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
