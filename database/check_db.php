<?php

// Este archivo es para verificar la estructura de la base de datos
// Puedes ejecutarlo con: php artisan tinker --execute="require 'database/check_db.php';"

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Verificar conexión a la base de datos
try {
    $connection = DB::connection()->getPdo();
    echo "✓ Conexión a la base de datos establecida correctamente: " . 
         DB::connection()->getDatabaseName() . "\n";
} catch (\Exception $e) {
    echo "✗ Error de conexión a la base de datos: " . $e->getMessage() . "\n";
    return 1;
}

// Tablas que deberían existir
$tables = ['carts', 'cart_items', 'orders', 'order_items', 'products', 'categories'];

echo "Verificando tablas...\n";

$allTablesExist = true;
foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        echo "✓ Tabla '{$table}' existe\n";
        
        // Contar registros
        $count = DB::table($table)->count();
        echo "  - Registros: {$count}\n";
        
        // Mostrar columnas
        $columns = Schema::getColumnListing($table);
        echo "  - Columnas: " . implode(', ', $columns) . "\n";
    } else {
        echo "✗ Tabla '{$table}' NO existe\n";
        $allTablesExist = false;
    }
}

if (!$allTablesExist) {
    echo "ADVERTENCIA: Algunas tablas necesarias no existen. Ejecuta las migraciones con: php artisan migrate\n";
} else {
    echo "Todas las tablas necesarias existen.\n";
}

// Verificar estructura específica de tablas críticas
echo "Verificando estructura de tablas críticas...\n";

// Verificar orders
if (Schema::hasTable('orders')) {
    $orderColumns = Schema::getColumnListing('orders');
    $requiredOrderColumns = ['id', 'user_id', 'session_id', 'order_number', 'total', 'status', 'shipping_address', 'payment_method', 'name', 'email', 'phone', 'notes', 'created_at', 'updated_at'];
    
    $missingOrderColumns = array_diff($requiredOrderColumns, $orderColumns);
    
    if (empty($missingOrderColumns)) {
        echo "✓ Tabla orders tiene todas las columnas requeridas\n";
    } else {
        echo "✗ Tabla orders le faltan columnas: " . implode(', ', $missingOrderColumns) . "\n";
    }
}

// Verificar order_items
if (Schema::hasTable('order_items')) {
    $orderItemColumns = Schema::getColumnListing('order_items');
    $requiredOrderItemColumns = ['id', 'order_id', 'product_id', 'quantity', 'price', 'created_at', 'updated_at'];
    
    $missingOrderItemColumns = array_diff($requiredOrderItemColumns, $orderItemColumns);
    
    if (empty($missingOrderItemColumns)) {
        echo "✓ Tabla order_items tiene todas las columnas requeridas\n";
    } else {
        echo "✗ Tabla order_items le faltan columnas: " . implode(', ', $missingOrderItemColumns) . "\n";
    }
}

// Verificar permisos de escritura
echo "Verificando permisos de escritura...\n";

try {
    // Intentar insertar y eliminar un registro de prueba en la tabla carts
    $testId = DB::table('carts')->insertGetId([
        'session_id' => 'test_session_' . time(),
        'total' => 0,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "✓ Permiso de escritura en tabla carts verificado (ID: {$testId})\n";
    
    // Eliminar el registro de prueba
    DB::table('carts')->where('id', $testId)->delete();
    echo "✓ Permiso de eliminación en tabla carts verificado\n";
} catch (\Exception $e) {
    echo "✗ Error al verificar permisos de escritura: " . $e->getMessage() . "\n";
}

echo "Verificación completada.\n";
return 0;

