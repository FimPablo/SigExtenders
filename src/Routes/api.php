<?php

use FimPablo\SigExtenders\Http\Cosntrollers\SocketController;
use Illuminate\Support\Facades\Route;

Route::post('/socket', [SocketController::class]);