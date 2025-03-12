<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ProductController;

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

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);
Route::get('/blog', [BlogController::class, 'index']); // Mantener temporalmente para compatibilidad
Route::get('/blog/{id}', [BlogController::class, 'show']); // Mantener temporalmente para compatibilidad
Route::get('/categories', [CategoryController::class, 'index']);

// Nuevas rutas para productos
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
  Route::post('/logout', [AuthController::class, 'logout']);
  
  // Rutas de administración
  Route::post('/blog', [BlogController::class, 'store']); // Mantener temporalmente para compatibilidad
  Route::post('/blog/{id}', [BlogController::class, 'update']); // Mantener temporalmente para compatibilidad
  Route::delete('/blog/{id}', [BlogController::class, 'destroy']); // Mantener temporalmente para compatibilidad
  Route::post('/blog/{id}/review', [BlogController::class, 'addReview']); // Mantener temporalmente para compatibilidad
  
  // Nuevas rutas para productos
  Route::post('/products', [ProductController::class, 'store']);
  Route::put('/products/{id}', [ProductController::class, 'update']);
  Route::delete('/products/{id}', [ProductController::class, 'destroy']);
  
  Route::post('/categories', [CategoryController::class, 'store']);
  Route::put('/categories/{id}', [CategoryController::class, 'update']);
  Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
  
  Route::get('/admin/stats', [AdminController::class, 'getStats']);
  Route::get('/admin/orders/recent', [AdminController::class, 'getRecentOrders']);
  
  // Rutas para administradores
  Route::get('/admin/users', [AdminUserController::class, 'index']);
  Route::post('/admin/users', [AdminUserController::class, 'store']);
  Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
  return $request->user();
});

