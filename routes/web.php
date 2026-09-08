<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\RoomController;

Route::get('/', function () {
    return view('welcome');
});

Route::apiResource('rooms', RoomController::class);