<?php
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\BookingController;

Route::apiResource('rooms', RoomController::class);

Route::post('/bookings', [BookingController::class, 'store'])
    ->middleware('resolve.user');
    
Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])
    ->middleware('resolve.user');