<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Api\ProductController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas para órdenes
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/{order}', [OrderController::class, 'show']);

// Rutas para productos
Route::apiResource('products', ProductController::class)->only(['index', 'show']);
Route::apiResource('products', ProductController::class)->except(['index', 'show'])->middleware(['auth:sanctum', 'admin']);

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