<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\MeetingRoomController;

Route::get('/', [MeetingRoomController::class, 'index'])->name('meeting.index');
Route::post('/rooms', [MeetingRoomController::class, 'storeRoom'])->name('room.store');
Route::put('/rooms/{room}', [MeetingRoomController::class, 'updateRoom'])->name('room.update');
Route::delete('/rooms/{room}', [MeetingRoomController::class, 'destroyRoom'])->name('room.destroy');
Route::post('/bookings', [MeetingRoomController::class, 'storeBooking'])->name('booking.store');
Route::patch('/bookings/{booking}/cancel', [MeetingRoomController::class, 'cancelBooking'])->name('booking.cancel');