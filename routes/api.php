<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\BookingController;

Route::apiResource('rooms', RoomController::class);

Route::middleware('resolve.user')->group(function () {
    Route::get('/rooms/{room}/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']);
    
});
Route::get('/rooms/search', [RoomController::class, 'searchAvailable'])->name('room.search');