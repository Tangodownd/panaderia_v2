<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/* Route::get('/', function () {
    return view('welcome');
}); */

Route::get('{any}', function () {
    return view('app');
})->where('any', '.*');

// Ruta para el panel de administración
Route::get('/admin{any}', function () {
    return view('welcome');
})->where('any', '.*');

// Ruta para la tienda
Route::get('/{any}', function () {
    return view('tienda');
})->where('any', '.*');