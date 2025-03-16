<?php
// Este script realiza un diagnóstico completo del proceso de órdenes
// Colócalo en la carpeta public y ejecútalo desde el navegador

// Cargar el framework de Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;

// Habilitar el modo de depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Función para mostrar mensajes de error o éxito
function showMessage($message, $type = 'info') {
    $class = 'alert-info';
    if ($type == 'error') $class = 'alert-danger';
    if ($type == 'success') $class = 'alert-success';
    if ($type == 'warning') $class = 'alert-warning';
    
    echo "<div class='alert $class'>$message</div>";
}

// Función para ejecutar una consulta SQL directa y mostrar resultados
function executeQuery($sql, $params = []) {
    try {
        $results = DB::select($sql, $params);
        return $results;
    } catch (\Exception $e) {
        showMessage("Error al ejecutar consulta: " . $e->getMessage(), 'error');
        return [];
    }
}

// Función para verificar permisos de escritura en una tabla
function checkTableWritePermissions($table) {
    try {
        // Intentar insertar un registro de prueba
        $testId = DB::table($table)->insertGetId([
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Si llegamos aquí, la inserción fue exitosa
        // Eliminar el registro de prueba
        DB::table($table)->where('id', $testId)->delete();
        
        return true;
    } catch (\Exception $e) {
        return false;
    }
}

// Función para crear una orden de prueba directamente
function createTestOrder() {
    try {
        // Iniciar transacción
        DB::beginTransaction();
        
        // Crear una nueva orden
        $order = new Order();
        $order->user_id = null;
        $order->session_id = Str::uuid()->toString();
        $order->total = 100.00;
        $order->status = 'pending';
        $order->shipping_address = 'Dirección de prueba';
        $order->payment_method = 'cash';
        $order->name = 'Usuario de Prueba';
        $order->email = 'test@example.com';
        $order->phone = '123456789';
        $order->notes = 'Orden de prueba creada desde debug_order_process.php';
        
        // Guardar la orden
        $saved = $order->save();
        
        if (!$saved) {
            throw new \Exception("No se pudo guardar la orden");
        }
        
        // Obtener un producto para la orden
        $product = Product::first();
        
        if (!$product) {
            throw new \Exception("No hay productos disponibles");
        }
        
        // Crear un item de orden
        $orderItem = new OrderItem();
        $orderItem->order_id = $order->id;
        $orderItem->product_id = $product->id;
        $orderItem->quantity = 1;
        $orderItem->price = $product->price;
        $orderItem->save();
        
        // Confirmar transacción
        DB::commit();
        
        return [
            'success' => true,
            'order' => $order,
            'item' => $orderItem
        ];
    } catch (\Exception $e) {
        // Revertir transacción en caso de error
        DB::rollBack();
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Función para crear un carrito de prueba directamente
function createTestCart() {
    try {
        // Crear un nuevo carrito
        $cart = new Cart();
        $cart->session_id = Str::uuid()->toString();
        $cart->user_id = null;
        $cart->total = 0;
        
        // Guardar el carrito
        $saved = $cart->save();
        
        if (!$saved) {
            throw new \Exception("No se pudo guardar el carrito");
        }
        
        // Obtener un producto para el carrito
        $product = Product::first();
        
        if (!$product) {
            throw new \Exception("No hay productos disponibles");
        }
        
        // Crear un item de carrito
        $cartItem = new CartItem();
        $cartItem->cart_id = $cart->id;
        $cartItem->product_id = $product->id;
        $cartItem->quantity = 1;
        $cartItem->price = $product->price;
        $cartItem->save();
        
        // Actualizar el total del carrito
        $cart->total = $product->price;
        $cart->save();
        
        return [
            'success' => true,
            'cart' => $cart,
            'item' => $cartItem
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Función para verificar la estructura de una tabla
function checkTableStructure($table) {
    if (!Schema::hasTable($table)) {
        return [
            'exists' => false,
            'columns' => []
        ];
    }
    
    $columns = Schema::getColumnListing($table);
    $structure = [];
    
    foreach ($columns as $column) {
        $type = DB::getSchemaBuilder()->getColumnType($table, $column);
        $structure[$column] = $type;
    }
    
    return [
        'exists' => true,
        'columns' => $structure
    ];
}

// Función para verificar las relaciones entre tablas
function checkTableRelations() {
    $relations = [];
    
    // Verificar relación Cart -> CartItem
    $cartItems = DB::table('cart_items')
        ->join('carts', 'cart_items.cart_id', '=', 'carts.id')
        ->select('cart_items.id', 'cart_items.cart_id', 'carts.id as cart_real_id')
        ->limit(5)
        ->get();
    
    $relations['cart_to_items'] = [
        'success' => count($cartItems) > 0,
        'sample' => $cartItems
    ];
    
    // Verificar relación Order -> OrderItem
    $orderItems = DB::table('order_items')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->select('order_items.id', 'order_items.order_id', 'orders.id as order_real_id')
        ->limit(5)
        ->get();
    
    $relations['order_to_items'] = [
        'success' => count($orderItems) > 0,
        'sample' => $orderItems
    ];
    
    return $relations;
}

// Función para verificar los triggers de la base de datos
function checkDatabaseTriggers() {
    $triggers = DB::select("SHOW TRIGGERS");
    return $triggers;
}

// Función para verificar los procedimientos almacenados
function checkStoredProcedures() {
    $procedures = DB::select("SHOW PROCEDURE STATUS WHERE Db = DATABASE()");
    return $procedures;
}

// Función para verificar las restricciones de clave foránea
function checkForeignKeyConstraints() {
    $constraints = [];
    
    // Verificar restricciones en cart_items
    $cartItemConstraints = DB::select("
        SELECT 
            TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE
            REFERENCED_TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'cart_items'
    ");
    
    $constraints['cart_items'] = $cartItemConstraints;
    
    // Verificar restricciones en order_items
    $orderItemConstraints = DB::select("
        SELECT 
            TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE
            REFERENCED_TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'order_items'
    ");
    
    $constraints['order_items'] = $orderItemConstraints;
    
    return $constraints;
}

// Función para verificar los índices de las tablas
function checkTableIndexes($table) {
    $indexes = DB::select("SHOW INDEX FROM $table");
    return $indexes;
}

// Función para verificar el motor de almacenamiento de las tablas
function checkTableEngine($table) {
    $engine = DB::select("SHOW TABLE STATUS WHERE Name = '$table'")[0]->Engine;
    return $engine;
}

// Función para verificar los permisos del usuario de la base de datos
function checkDatabaseUserPermissions() {
    $permissions = DB::select("SHOW GRANTS FOR CURRENT_USER()");
    return $permissions;
}

// Función para verificar el estado de las transacciones
function checkTransactionStatus() {
    $inTransaction = DB::transactionLevel() > 0;
    return $inTransaction;
}

// Función para verificar el estado de las conexiones a la base de datos
function checkDatabaseConnections() {
    $connections = DB::select("SHOW PROCESSLIST");
    return $connections;
}

// Función para verificar el estado de la base de datos
function checkDatabaseStatus() {
    $status = DB::select("SHOW STATUS");
    return $status;
}

// Función para verificar el estado de las variables de la base de datos
function checkDatabaseVariables() {
    $variables = DB::select("SHOW VARIABLES");
    return $variables;
}

// Función para verificar el estado de los logs de la base de datos
function checkDatabaseLogs() {
    $logs = DB::select("SHOW VARIABLES LIKE 'log%'");
    return $logs;
}

// Función para verificar el estado de las tablas
function checkTableStatus() {
    $tables = DB::select("SHOW TABLE STATUS");
    return $tables;
}

// Función para verificar el estado de las columnas de las tablas
function checkTableColumns($table) {
    $columns = DB::select("SHOW COLUMNS FROM $table");
    return $columns;
}

// Función para verificar el estado de los registros de las tablas
function checkTableRecords($table) {
    $count = DB::table($table)->count();
    $records = DB::table($table)->orderBy('id', 'desc')->limit(5)->get();
    return [
        'count' => $count,
        'records' => $records
    ];
}

// Función para verificar el estado de los registros de las tablas con relaciones
function checkTableRecordsWithRelations($table, $relation, $relationColumn, $relationTable) {
    $records = DB::table($table)
        ->join($relationTable, "$table.$relation", '=', "$relationTable.id")
        ->select("$table.*", "$relationTable.$relationColumn")
        ->orderBy("$table.id", 'desc')
        ->limit(5)
        ->get();
    
    return $records;
}

// Función para verificar el estado de los registros de las tablas con relaciones múltiples
function checkTableRecordsWithMultipleRelations($table, $relations) {
    $query = DB::table($table);
    
    foreach ($relations as $relation) {
        $query->join($relation['table'], "$table.{$relation['column']}", '=', "{$relation['table']}.id");
    }
    
    $query->select("$table.*");
    
    foreach ($relations as $relation) {
        $query->addSelect("{$relation['table']}.{$relation['select']} as {$relation['alias']}");
    }
    
    $query->orderBy("$table.id", 'desc');
    $query->limit(5);
    
    $records = $query->get();
    
    return $records;
}

// Función para verificar el estado de los registros de las tablas con relaciones múltiples y condiciones
function checkTableRecordsWithMultipleRelationsAndConditions($table, $relations, $conditions) {
    $query = DB::table($table);
    
    foreach ($relations as $relation) {
        $query->join($relation['table'], "$table.{$relation['column']}", '=', "{$relation['table']}.id");
    }
    
    $query->select("$table.*");
    
    foreach ($relations as $relation) {
        $query->addSelect("{$relation['table']}.{$relation['select']} as {$relation['alias']}");
    }
    
    foreach ($conditions as $condition) {
        $query->where($condition['column'], $condition['operator'], $condition['value']);
    }
    
    $query->orderBy("$table.id", 'desc');
    $query->limit(5);
    
    $records = $query->get();
    
    return $records;
}

// Iniciar la salida HTML
echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Diagnóstico del Proceso de Órdenes</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            max-height: 300px;
            overflow-y: auto;
        }
        .card {
            margin-bottom: 20px;
        }
        .table-responsive {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class='container py-5'>
        <h1>Diagnóstico del Proceso de Órdenes</h1>";

// Verificar si se ha enviado el formulario para crear una orden de prueba
if (isset($_POST['create_test_order'])) {
    $result = createTestOrder();
    
    echo "<div class='card mb-4'>
        <div class='card-header bg-primary text-white'>
            <h5 class='mb-0'>Resultado de la Creación de Orden de Prueba</h5>
        </div>
        <div class='card-body'>";
    
    if ($result['success']) {
        showMessage("Orden de prueba creada con éxito. ID: {$result['order']->id}", 'success');
        echo "<h6>Detalles de la Orden:</h6>";
        echo "<pre>" . print_r($result['order']->toArray(), true) . "</pre>";
        echo "<h6>Detalles del Item:</h6>";
        echo "<pre>" . print_r($result['item']->toArray(), true) . "</pre>";
    } else {
        showMessage("Error al crear la orden de prueba: {$result['error']}", 'error');
        echo "<h6>Traza del Error:</h6>";
        echo "<pre>{$result['trace']}</pre>";
    }
    
    echo "</div></div>";
}

// Verificar si se ha enviado el formulario para crear un carrito de prueba
if (isset($_POST['create_test_cart'])) {
    $result = createTestCart();
    
    echo "<div class='card mb-4'>
        <div class='card-header bg-primary text-white'>
            <h5 class='mb-0'>Resultado de la Creación de Carrito de Prueba</h5>
        </div>
        <div class='card-body'>";
    
    if ($result['success']) {
        showMessage("Carrito de prueba creado con éxito. ID: {$result['cart']->id}", 'success');
        echo "<h6>Detalles del Carrito:</h6>";
        echo "<pre>" . print_r($result['cart']->toArray(), true) . "</pre>";
        echo "<h6>Detalles del Item:</h6>";
        echo "<pre>" . print_r($result['item']->toArray(), true) . "</pre>";
    } else {
        showMessage("Error al crear el carrito de prueba: {$result['error']}", 'error');
        echo "<h6>Traza del Error:</h6>";
        echo "<pre>{$result['trace']}</pre>";
    }
    
    echo "</div></div>";
}

// Verificar si se ha enviado el formulario para ejecutar una consulta SQL
if (isset($_POST['execute_query']) && !empty($_POST['sql_query'])) {
    $sql = $_POST['sql_query'];
    $results = executeQuery($sql);
    
    echo "<div class='card mb-4'>
        <div class='card-header bg-primary text-white'>
            <h5 class='mb-0'>Resultado de la Consulta SQL</h5>
        </div>
        <div class='card-body'>";
    
    echo "<h6>Consulta ejecutada:</h6>";
    echo "<pre>$sql</pre>";
    
    echo "<h6>Resultados:</h6>";
    echo "<pre>" . print_r($results, true) . "</pre>";
    
    echo "</div></div>";
}

// Mostrar formulario para crear una orden de prueba
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Crear Orden de Prueba</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <p>Esta acción intentará crear una orden de prueba directamente en la base de datos.</p>
            <input type='hidden' name='create_test_order' value='1'>
            <button type='submit' class='btn btn-success'>Crear Orden de Prueba</button>
        </form>
    </div>
</div>";

// Mostrar formulario para crear un carrito de prueba
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Crear Carrito de Prueba</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <p>Esta acción intentará crear un carrito de prueba directamente en la base de datos.</p>
            <input type='hidden' name='create_test_cart' value='1'>
            <button type='submit' class='btn btn-success'>Crear Carrito de Prueba</button>
        </form>
    </div>
</div>";

// Mostrar formulario para ejecutar una consulta SQL
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Ejecutar Consulta SQL</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <div class='mb-3'>
                <label for='sql_query' class='form-label'>Consulta SQL:</label>
                <textarea class='form-control' id='sql_query' name='sql_query' rows='3' required></textarea>
            </div>
            <input type='hidden' name='execute_query' value='1'>
            <button type='submit' class='btn btn-primary'>Ejecutar Consulta</button>
        </form>
    </div>
</div>";

// Verificar la estructura de las tablas
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Estructura de las Tablas</h5>
    </div>
    <div class='card-body'>";

$tables = ['carts', 'cart_items', 'orders', 'order_items'];

foreach ($tables as $table) {
    $structure = checkTableStructure($table);
    
    echo "<h6>Tabla: $table</h6>";
    
    if ($structure['exists']) {
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped table-sm'>";
        echo "<thead><tr><th>Columna</th><th>Tipo</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($structure['columns'] as $column => $type) {
            echo "<tr><td>$column</td><td>$type</td></tr>";
        }
        
        echo "</tbody></table>";
        echo "</div>";
    } else {
        showMessage("La tabla $table no existe", 'error');
    }
}

echo "</div></div>";

// Verificar los registros de las tablas
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Registros de las Tablas</h5>
    </div>
    <div class='card-body'>";

foreach ($tables as $table) {
    $records = checkTableRecords($table);
    
    echo "<h6>Tabla: $table (Total: {$records['count']})</h6>";
    
    if ($records['count'] > 0) {
        echo "<pre>" . print_r($records['records'], true) . "</pre>";
    } else {
        showMessage("No hay registros en la tabla $table", 'warning');
    }
}

echo "</div></div>";

// Verificar las relaciones entre tablas
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Relaciones entre Tablas</h5>
    </div>
    <div class='card-body'>";

$relations = checkTableRelations();

echo "<h6>Relación Cart -> CartItem</h6>";
if ($relations['cart_to_items']['success']) {
    echo "<pre>" . print_r($relations['cart_to_items']['sample'], true) . "</pre>";
} else {
    showMessage("No se encontraron relaciones entre carts y cart_items", 'warning');
}

echo "<h6>Relación Order -> OrderItem</h6>";
if ($relations['order_to_items']['success']) {
    echo "<pre>" . print_r($relations['order_to_items']['sample'], true) . "</pre>";
} else {
    showMessage("No se encontraron relaciones entre orders y order_items", 'warning');
}

echo "</div></div>";

// Verificar los permisos de escritura en las tablas
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Permisos de Escritura en las Tablas</h5>
    </div>
    <div class='card-body'>";

foreach ($tables as $table) {
    $hasPermission = checkTableWritePermissions($table);
    
    echo "<h6>Tabla: $table</h6>";
    
    if ($hasPermission) {
        showMessage("Se tienen permisos de escritura en la tabla $table", 'success');
    } else {
        showMessage("No se tienen permisos de escritura en la tabla $table", 'error');
    }
}

echo "</div></div>";

// Verificar las restricciones de clave foránea
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Restricciones de Clave Foránea</h5>
    </div>
    <div class='card-body'>";

$constraints = checkForeignKeyConstraints();

echo "<h6>Restricciones en cart_items</h6>";
if (count($constraints['cart_items']) > 0) {
    echo "<pre>" . print_r($constraints['cart_items'], true) . "</pre>";
} else {
    showMessage("No se encontraron restricciones de clave foránea en cart_items", 'warning');
}

echo "<h6>Restricciones en order_items</h6>";
if (count($constraints['order_items']) > 0) {
    echo "<pre>" . print_r($constraints['order_items'], true) . "</pre>";
} else {
    showMessage("No se encontraron restricciones de clave foránea en order_items", 'warning');
}

echo "</div></div>";

// Verificar los índices de las tablas
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Índices de las Tablas</h5>
    </div>
    <div class='card-body'>";

foreach ($tables as $table) {
    $indexes = checkTableIndexes($table);
    
    echo "<h6>Tabla: $table</h6>";
    
    if (count($indexes) > 0) {
        echo "<pre>" . print_r($indexes, true) . "</pre>";
    } else {
        showMessage("No se encontraron índices en la tabla $table", 'warning');
    }
}

echo "</div></div>";

// Verificar el motor de almacenamiento de las tablas
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Motor de Almacenamiento de las Tablas</h5>
    </div>
    <div class='card-body'>";

foreach ($tables as $table) {
    $engine = checkTableEngine($table);
    
    echo "<h6>Tabla: $table</h6>";
    echo "<p>Motor: $engine</p>";
}

echo "</div></div>";

// Verificar el estado de las transacciones
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Estado de las Transacciones</h5>
    </div>
    <div class='card-body'>";

$inTransaction = checkTransactionStatus();

if ($inTransaction) {
    showMessage("Hay una transacción activa", 'warning');
} else {
    showMessage("No hay transacciones activas", 'success');
}

echo "</div></div>";

// Verificar el estado de las conexiones a la base de datos
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Estado de las Conexiones a la Base de Datos</h5>
    </div>
    <div class='card-body'>";

$connections = checkDatabaseConnections();

echo "<pre>" . print_r($connections, true) . "</pre>";

echo "</div></div>";

// Verificar el estado de las tablas
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Estado de las Tablas</h5>
    </div>
    <div class='card-body'>";

$tableStatus = checkTableStatus();

echo "<pre>" . print_r($tableStatus, true) . "</pre>";

echo "</div></div>";

// Finalizar la salida HTML
echo "</div>
</body>
</html>";
?>

