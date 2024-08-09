<?php

use FimPablo\SigExtenders\Http\Controllers\SocketController;
use Illuminate\Support\Facades\Route;

Route::post('/socket', [SocketController::class]);