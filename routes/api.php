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
use App\Http\Controllers\ChatCartController;

/*
|--------------------------------------------------------------------------
| API Routes - Públicas
|--------------------------------------------------------------------------
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
| Carrito (público, con sesión)
| Usa los NOMBRES DE MÉTODOS QUE YA TENÍAS: index/add/update/remove/clear
|--------------------------------------------------------------------------
*/
Route::prefix('cart')->group(function () {
    // Leer carrito
    Route::get('/', [CartController::class, 'index']);

    // Añadir ítem
    Route::post('/add', [CartController::class, 'add']);

    // Actualizar cantidad (compatibilidad: POST clásico y PUT por itemId)
    Route::post('/update', [CartController::class, 'update']);
    Route::put('/items/{id}', [CartController::class, 'update']); // opcional compat

    // Eliminar ítem (compatibilidad: POST clásico y DELETE por itemId)
    Route::post('/remove', [CartController::class, 'remove']);
    Route::delete('/items/{id}', [CartController::class, 'remove']); // opcional compat

    // Vaciar carrito (compatibilidad: POST y DELETE)
    Route::post('/clear', [CartController::class, 'clear']);
    Route::delete('/', [CartController::class, 'clear']); // opcional compat

    // Auxiliares
    Route::post('/remove-out-of-stock', [CartController::class, 'removeOutOfStock'])->name('cart.removeOutOfStock');
    Route::post('/mark-as-completed', [CartController::class, 'markAsCompleted'])->name('cart.markAsCompleted');
});

/*
|--------------------------------------------------------------------------
| Chat (validación stock real / confirmación con reserva)
|--------------------------------------------------------------------------
*/
Route::prefix('chat')->group(function () {
    Route::post('/cart/items', [ChatCartController::class, 'checkItems']); // valida stock real (lote)
    Route::post('/orders/confirm', [ChatCartController::class, 'confirm']); // reserva + crea orden (TTL)
});

/*
|--------------------------------------------------------------------------
| Órdenes (público)
|--------------------------------------------------------------------------
*/
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{id}', [OrderController::class, 'getOrderDetails']);


// Subida de comprobante (auto-confirma si NO es efectivo)
Route::post('/orders/{id}/payment-proof', [OrderController::class, 'uploadPaymentProof']);

/*
|--------------------------------------------------------------------------
| Rutas autenticadas (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Sesión
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Confirmación manual de pago (efectivo / corrección)
    Route::post('/orders/{id}/confirm-payment', [OrderController::class, 'confirmPayment']);

    // Blog (CRUD)
    Route::post('/blog', [BlogController::class, 'store']);
    Route::post('/blog/{id}', [BlogController::class, 'update']); // si prefieres, cambia a PUT
    Route::delete('/blog/{id}', [BlogController::class, 'destroy']);
    Route::post('/blog/{id}/review', [BlogController::class, 'addReview']);

    // Productos web (CRUD)
    Route::post('/products', [WebProductController::class, 'store']);
    Route::put('/products/{id}', [WebProductController::class, 'update']);
    Route::delete('/products/{id}', [WebProductController::class, 'destroy']);

    // Categorías (CRUD)
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Admin
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
