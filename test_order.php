<?php
// Este archivo es para probar la creación de órdenes directamente
// Puedes ejecutarlo con: php test_order.php

// Cargar el entorno de Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;

echo "Iniciando prueba de creación de orden...\n";

// Verificar conexión a la base de datos
try {
    $connection = DB::connection()->getPdo();
    echo "✓ Conexión a la base de datos establecida correctamente: " . 
         DB::connection()->getDatabaseName() . "\n";
} catch (\Exception $e) {
    echo "✗ Error de conexión a la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}

// Verificar si hay productos en la base de datos
$products = Product::take(1)->get();
if ($products->isEmpty()) {
    echo "✗ No hay productos en la base de datos para probar\n";
    exit(1);
}

$product = $products->first();
echo "✓ Producto encontrado para prueba: " . $product->name . " (ID: " . $product->id . ")\n";

// Generar datos de prueba
$sessionId = Str::uuid()->toString();
$cartTotal = $product->price;

echo "Creando orden de prueba con session_id: " . $sessionId . "\n";

// Usar una transacción para poder revertir los cambios al final
DB::beginTransaction();

try {
    // 1. Crear un carrito
    $cart = new Cart();
    $cart->session_id = $sessionId;
    $cart->total = $cartTotal;
    $cart->save();
    
    echo "✓ Carrito creado con ID: " . $cart->id . "\n";
    
    // 2. Crear un item de carrito
    $cartItem = new CartItem();
    $cartItem->cart_id = $cart->id;
    $cartItem->product_id = $product->id;
    $cartItem->quantity = 1;
    $cartItem->price = $product->price;
    $cartItem->save();
    
    echo "✓ Item de carrito creado con ID: " . $cartItem->id . "\n";
    
    // 3. Crear una orden
    $order = new Order();
    $order->session_id = $sessionId;
    $order->total = $cartTotal;
    $order->status = 'pending';
    $order->shipping_address = 'Dirección de prueba';
    $order->payment_method = 'cash';
    $order->name = 'Usuario de Prueba';
    $order->email = 'test@example.com';
    $order->phone = '1234567890';
    
    // Verificar si la columna order_number existe
    if (Schema::hasColumn('orders', 'order_number')) {
        $order->order_number = 'TEST-' . time();
        echo "✓ Columna order_number existe, añadiendo a los datos\n";
    } else {
        echo "✗ Columna order_number NO existe\n";
    }
    
    $order->save();
    
    echo "✓ Orden creada con ID: " . $order->id . "\n";
    
    // 4. Crear un item de orden
    $orderItem = new OrderItem();
    $orderItem->order_id = $order->id;
    $orderItem->product_id = $product->id;
    $orderItem->quantity = 1;
    $orderItem->price = $product->price;
    $orderItem->save();
    
    echo "✓ Item de orden creado con ID: " . $orderItem->id . "\n";
    
    echo "¿Deseas mantener estos cambios en la base de datos? (s/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) == 's') {
        DB::commit();
        echo "✓ Cambios guardados en la base de datos\n";
    } else {
        DB::rollBack();
        echo "✓ Cambios revertidos (rollback)\n";
    }
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "✗ Error al crear la orden: " . $e->getMessage() . "\n";
    echo "  Archivo: " . $e->getFile() . " (línea " . $e->getLine() . ")\n";
}

echo "\nPrueba completada.\n";

