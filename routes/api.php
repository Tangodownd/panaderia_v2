<?php

use Illuminate\Http\Request;
 use Illuminate\Support\Facades\Route;
 use App\Http\Controllers\BlogController;
 use App\Http\Controllers\WebProductController;
 use App\Http\Controllers\CategoryController;
 use App\Http\Controllers\AuthController;
 use App\Http\Controllers\AdminController;
 use App\Http\Controllers\AdminUserController;
 
 use App\Http\Controllers\CartController;
 use App\Http\Controllers\OrderController;
 use App\Http\Controllers\DebugOrderController;
 use App\Http\Controllers\TestOrderController;
 use App\Http\Controllers\DirectOrderController;
 use App\Http\Controllers\Api\ProductController as ApiProductController;

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
Route::get('/products', [WebProductController::class, 'index']);
Route::get('/products/{id}', [WebProductController::class, 'show']);


// Rutas para el carrito y órdenes (con middleware web para acceso a cookies)
 
     // Rutas para el carrito
     Route::get('/cart', [CartController::class, 'getCart']);
     Route::post('/cart/add', [CartController::class, 'addToCart']);
     Route::put('/cart/items/{id}', [CartController::class, 'updateCartItem']);
     Route::delete('/cart/items/{id}', [CartController::class, 'removeFromCart']);
     Route::delete('/cart', [CartController::class, 'clearCart']);


     
Route::middleware('auth:sanctum')->group(function () {
     Route::post('/logout', [AuthController::class, 'logout']);

        // Rutas de administración
        Route::post('/blog', [BlogController::class, 'store']);
        Route::post('/blog/{id}', [BlogController::class, 'update']);
        Route::delete('/blog/{id}', [BlogController::class, 'destroy']);
        Route::post('/blog/{id}/review', [BlogController::class, 'addReview']);
    
        // Rutas para productos
        Route::post('/products', [WebProductController::class, 'store']);
        Route::put('/products/{id}', [WebProductController::class, 'update']);
        Route::delete('/products/{id}', [WebProductController::class, 'destroy']);
    
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    
        Route::get('/admin/stats', [AdminController::class, 'getStats']);
        Route::get('/admin/orders/recent', [AdminController::class, 'getRecentOrders']);     
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
});

            // Rutas para administradores
     Route::get('/admin/users', [AdminUserController::class, 'index']);
     Route::post('/admin/users', [AdminUserController::class, 'store']);
     Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy']);
     
     Route::get('/user', function (Request $request) {
         return $request->user();
// Rutas de productos web (usando el controlador web)
Route::get('/products', [WebProductController::class, 'index']);
Route::get('/products/{id}', [WebProductController::class, 'show']);

// Rutas de productos API (usando el controlador en Api namespace)
Route::prefix('v1')->group(function () {
    Route::get('/products', [ApiProductController::class, 'index']);
    Route::get('/products/{id}', [ApiProductController::class, 'show']);
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/products', [ApiProductController::class, 'store']);
        Route::put('/products/{id}', [ApiProductController::class, 'update']);
        Route::delete('/products/{id}', [ApiProductController::class, 'destroy']);
    });
});
});

// Rutas de categorías
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// Rutas del carrito
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart/add', [CartController::class, 'add']);
Route::post('/cart/remove', [CartController::class, 'remove']);
Route::post('/cart/update', [CartController::class, 'update']);
Route::post('/cart/clear', [CartController::class, 'clear']);
Route::post('/cart/mark-completed', [CartController::class, 'markAsCompleted']);

// Rutas de pedidos
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/user', [OrderController::class, 'getUserOrders']);
Route::get('/orders/{id}', [OrderController::class, 'getOrderDetails']);

