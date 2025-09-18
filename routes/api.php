<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\WebProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Api\ProductController as ApiProductController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Rutas públicas (sin autenticación)
*/
Route::post('/login', [AuthController::class, 'login']);

Route::get('/blog', [BlogController::class, 'index']);
Route::get('/blog/{id}', [BlogController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

Route::get('/products', [WebProductController::class, 'index']);
Route::get('/products/{id}', [WebProductController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Órdenes (público o mixto, según tu flujo)
| - Dejamos create (store) público como tenías.
| - Los endpoints ligados al usuario sí van autenticados (abajo).
*/
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{id}', [OrderController::class, 'getOrderDetails']);

/*
|--------------------------------------------------------------------------
| Autenticadas con Sanctum
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Session / usuario autenticado
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // --- Carrito (MOVIDO AQUÍ: protegido por auth Sanctum) ---
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'getCart']);               // o index()
        Route::post('/add', [CartController::class, 'addToCart']);         // o add()
        Route::put('/items/{id}', [CartController::class, 'updateCartItem']);   // o update()
        Route::delete('/items/{id}', [CartController::class, 'removeFromCart']); // o remove()
        Route::delete('/', [CartController::class, 'clearCart']);          // o clear()
        Route::post('/remove-out-of-stock', [CartController::class, 'removeOutOfStock']);
        Route::post('/mark-as-completed', [CartController::class, 'markAsCompleted']);
    });

    // Blog (CRUD protegido)
    Route::post('/blog', [BlogController::class, 'store']);
    Route::post('/blog/{id}', [BlogController::class, 'update']); // si prefieres, cámbialo a PUT
    Route::delete('/blog/{id}', [BlogController::class, 'destroy']);
    Route::post('/blog/{id}/review', [BlogController::class, 'addReview']);

    // Productos web (CRUD protegido)
    Route::post('/products', [WebProductController::class, 'store']);
    Route::put('/products/{id}', [WebProductController::class, 'update']);
    Route::delete('/products/{id}', [WebProductController::class, 'destroy']);

    // Categorías (CRUD protegido)
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Admin (stats, órdenes recientes)
    Route::get('/admin/stats', [AdminController::class, 'getStats']);
    Route::get('/admin/orders/recent', [AdminController::class, 'getRecentOrders']);

    // Solo administradores
    Route::middleware('admin')->group(function () {
        Route::get('/admin/users', [AdminUserController::class, 'index']);
        Route::post('/admin/users', [AdminUserController::class, 'store']);
        Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy']);
    });

    // Órdenes del usuario autenticado
    Route::get('/orders/user', [OrderController::class, 'getUserOrders']);
});

/*
|--------------------------------------------------------------------------
| API v1 (versionada)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    Route::get('/products', [ApiProductController::class, 'index']);
    Route::get('/products/{id}', [ApiProductController::class, 'show']);

    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/products', [ApiProductController::class, 'store']);
        Route::put('/products/{id}', [ApiProductController::class, 'update']);
        Route::delete('/products/{id}', [ApiProductController::class, 'destroy']);
    });
});
