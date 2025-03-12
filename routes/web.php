<?php

use Illuminate\Support\Facades\Route;

// Ruta para el panel de administración (debe ir PRIMERO)
Route::get('/admin/{any?}', function () {
    return view('admin');
})->where('any', '.*');

// Ruta para la tienda (debe ir DESPUÉS de admin)
Route::get('/{any?}', function () {
    return view('tienda');
})->where('any', '.*');

