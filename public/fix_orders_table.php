<?php

// Este script corrige el auto-incremento de la tabla orders
// Colócalo en la carpeta public y ejecútalo desde el navegador

// Cargar el framework de Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "<h1>Reparación de la tabla orders</h1>";

try {
    // Verificar si la tabla existe
    if (!Schema::hasTable('orders')) {
        echo "<p style='color:red'>Error: La tabla 'orders' no existe.</p>";
        exit;
    }
    
    // Obtener información sobre la tabla
    echo "<h2>Información actual de la tabla</h2>";
    $tableStatus = DB::select("SHOW TABLE STATUS LIKE 'orders'")[0];
    echo "<pre>";
    print_r($tableStatus);
    echo "</pre>";
    
    // Verificar el último ID utilizado
    $lastOrder = DB::table('orders')->orderBy('id', 'desc')->first();
    $lastId = $lastOrder ? $lastOrder->id : 0;
    echo "<p>Último ID utilizado: {$lastId}</p>";
    
    // Obtener el valor actual de AUTO_INCREMENT
    $currentAutoIncrement = $tableStatus->Auto_increment;
    echo "<p>Valor actual de AUTO_INCREMENT: {$currentAutoIncrement}</p>";
    
    // Determinar el nuevo valor de AUTO_INCREMENT
    $newAutoIncrement = $lastId + 1;
    echo "<p>Nuevo valor de AUTO_INCREMENT: {$newAutoIncrement}</p>";
    
    // Corregir el AUTO_INCREMENT
    DB::statement("ALTER TABLE orders AUTO_INCREMENT = {$newAutoIncrement}");
    
    // Verificar que se haya aplicado el cambio
    $updatedTableStatus = DB::select("SHOW TABLE STATUS LIKE 'orders'")[0];
    echo "<h2>Información actualizada de la tabla</h2>";
    echo "<pre>";
    print_r($updatedTableStatus);
    echo "</pre>";
    
    echo "<p style='color:green'>La tabla 'orders' ha sido reparada correctamente.</p>";
    
    // Verificar si hay registros duplicados con ID 6
    $duplicates = DB::table('orders')->where('id', 6)->count();
    if ($duplicates > 1) {
        echo "<p style='color:orange'>Advertencia: Se encontraron {$duplicates} registros con ID 6. Esto no debería ocurrir en una tabla con clave primaria.</p>";
        
        // Mostrar los registros duplicados
        $duplicateRecords = DB::table('orders')->where('id', 6)->get();
        echo "<h3>Registros con ID 6:</h3>";
        echo "<pre>";
        print_r($duplicateRecords);
        echo "</pre>";
        
        // Opción para eliminar duplicados
        echo "<p>Para eliminar todos los registros con ID 6 excepto el más reciente, ejecuta:</p>";
        echo "<code>DELETE FROM orders WHERE id = 6 AND created_at < (SELECT MAX(created_at) FROM (SELECT * FROM orders) AS o WHERE id = 6);</code>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>

<p><a href="diagnose.php">Volver al diagnóstico</a></p>

