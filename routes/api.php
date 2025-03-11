<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//de esta forma SOLO nos genera las rutas de los metodos que estan en el only
//Route::resource('blog',App\Http\Controllers\BlogController::class)->only(['index','store','show','update','destroy']);

//de esta forma nos genera todas las rutas
Route::get('/blog', [BlogController::class, 'index']);
Route::resource('blog', BlogController::class);
Route::resource('blog',App\Http\Controllers\BlogController::class);
Route::resource('categories', CategoryController::class);
Route::get('/categories', [BlogController::class, 'getCategories']);
Route::get('/products/category/{category}', [BlogController::class, 'getProductsByCategory']);
Route::apiResource('blog', BlogController::class);
Route::apiResource('categories', CategoryController::class);
Route::post('/blog/{blog}/review', [BlogController::class, 'addReview']);
