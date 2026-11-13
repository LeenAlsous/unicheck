<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\LectureSessionController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\SectionController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Both roles: each sees only their own sections.
    Route::get('/sections', [SectionController::class, 'index']);
    Route::get('/sections/{section}', [SectionController::class, 'show']);

    Route::post('/check-ins', [CheckInController::class, 'store'])->middleware(['role:student', 'throttle:20,1']);
    Route::get('/devices', [DeviceController::class, 'index'])->middleware('role:student');
    Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->middleware('role:student');

    Route::middleware('role:instructor,admin')->group(function () {
        Route::get('/rooms', [RoomController::class, 'index']);
        Route::get('/sections/{section}/students', [SectionController::class, 'students']);
        Route::get('/sections/{section}/sessions', [LectureSessionController::class, 'index']);
        Route::post('/sections/{section}/sessions', [LectureSessionController::class, 'store']);
        Route::get('/sessions/{session}', [LectureSessionController::class, 'show']);
        Route::post('/sessions/{session}/close', [LectureSessionController::class, 'close']);
        Route::get('/sessions/{session}/attendance', [AttendanceController::class, 'index']);
        Route::put('/sessions/{session}/attendance/{student}', [AttendanceController::class, 'update']);
    });
});
