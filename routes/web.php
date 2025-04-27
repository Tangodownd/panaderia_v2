<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;

// Rutas específicas para archivos estáticos de términos y condiciones
Route::get('/terminos.html', function () {
    return view('terminos');
});

Route::get('/privacidad.html', function () {
    return view('privacidad');
});

// Ruta para el panel de administración (debe ir PRIMERO)
Route::get('/admin/{any?}', function () {
    return view('admin');
})->where('any', '.*');

// Ruta para la tienda (debe ir DESPUÉS de admin y ÚLTIMA)
Route::get('/{any?}', function () {
    return view('tienda');
})->where('any', '.*');