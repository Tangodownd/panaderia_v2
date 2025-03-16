<?php
// Este archivo es para depuración directa
// Puedes ejecutarlo con: php debug.php

// Cargar el entorno de Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

echo "Iniciando depuración de base de datos...\n";

// Verificar conexión a la base de datos
try {
    $connection = DB::connection()->getPdo();
    echo "✓ Conexión a la base de datos establecida correctamente: " . 
         DB::connection()->getDatabaseName() . "\n";
} catch (\Exception $e) {
    echo "✗ Error de conexión a la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}

// Verificar si la ruta /api/orders está registrada
$routes = Route::getRoutes();
$orderRouteExists = false;

foreach ($routes as $route) {
    if ($route->uri() == 'api/orders' && in_array('POST', $route->methods())) {
        $orderRouteExists = true;
        echo "✓ Ruta POST /api/orders encontrada\n";
        echo "  - Controlador: " . $route->getActionName() . "\n";
        break;
    }
}

if (!$orderRouteExists) {
    echo "✗ Ruta POST /api/orders NO encontrada\n";
}

// Verificar estructura de tablas
echo "\nVerificando estructura de tablas...\n";

$tables = ['orders', 'order_items', 'carts', 'cart_items', 'products'];
foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        echo "✓ Tabla '{$table}' existe\n";
        
        // Mostrar columnas
        $columns = Schema::getColumnListing($table);
        echo "  - Columnas: " . implode(', ', $columns) . "\n";
        
        // Contar registros
        $count = DB::table($table)->count();
        echo "  - Registros: {$count}\n";
    } else {
        echo "✗ Tabla '{$table}' NO existe\n";
    }
}

// Probar inserción directa en la base de datos
echo "\nProbando inserción directa en la base de datos...\n";

try {
    // Intentar insertar un registro de prueba en la tabla orders
    $orderData = [
        'session_id' => 'test_session_' . time(),
        'total' => 100,
        'status' => 'pending',
        'shipping_address' => 'Test Address',
        'payment_method' => 'cash',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'created_at' => now(),
        'updated_at' => now()
    ];
    
    // Verificar si la columna order_number existe
    if (Schema::hasColumn('orders', 'order_number')) {
        $orderData['order_number'] = 'TEST-' . time();
        echo "✓ Columna order_number existe, añadiendo a los datos\n";
    } else {
        echo "✗ Columna order_number NO existe\n";
    }
    
    $orderId = DB::table('orders')->insertGetId($orderData);
    
    echo "✓ Inserción exitosa en tabla orders (ID: {$orderId})\n";
    
    // Intentar insertar un registro de prueba en la tabla order_items
    $orderItemId = DB::table('order_items')->insertGetId([
        'order_id' => $orderId,
        'product_id' => 1, // Asegúrate de que este ID exista en la tabla products
        'quantity' => 1,
        'price' => 100,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "✓ Inserción exitosa en tabla order_items (ID: {$orderItemId})\n";
    
    // Eliminar los registros de prueba
    DB::table('order_items')->where('id', $orderItemId)->delete();
    DB::table('orders')->where('id', $orderId)->delete();
    
    echo "✓ Registros de prueba eliminados correctamente\n";
} catch (\Exception $e) {
    echo "✗ Error al probar inserción directa: " . $e->getMessage() . "\n";
}

echo "\nDepuración completada.\n";

