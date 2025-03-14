<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DebugOrderController;
use App\Http\Controllers\TestOrderController;
use App\Http\Controllers\DirectOrderController;

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
Route::get('/blog', [BlogController::class, 'index']);
Route::get('/blog/{id}', [BlogController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Rutas para el carrito y órdenes (con middleware web para acceso a cookies)

    // Rutas para el carrito
    Route::get('/cart', [CartController::class, 'getCart']);
    Route::post('/cart/add', [CartController::class, 'addToCart']);
    Route::put('/cart/items/{id}', [CartController::class, 'updateCartItem']);
    Route::delete('/cart/items/{id}', [CartController::class, 'removeFromCart']);
    Route::delete('/cart', [CartController::class, 'clearCart']);
    
    // Rutas para pedidos
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'getUserOrders']);
    Route::get('/orders/{id}', [OrderController::class, 'getOrderDetails']);
    
    // Rutas de depuración
    Route::post('/debug/create-order', [DebugOrderController::class, 'createOrder']);
    Route::get('/debug/all-orders', [DebugOrderController::class, 'getAllOrders']);
    Route::get('/debug/check-database', [DebugOrderController::class, 'checkDatabase']);
    Route::post('/test-order', [TestOrderController::class, 'testCreate']);
    Route::post('/direct-order', [DirectOrderController::class, 'createOrder']);
    


// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rutas de administración
    Route::post('/blog', [BlogController::class, 'store']);
    Route::post('/blog/{id}', [BlogController::class, 'update']);
    Route::delete('/blog/{id}', [BlogController::class, 'destroy']);
    Route::post('/blog/{id}/review', [BlogController::class, 'addReview']);

    // Rutas para productos
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
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

