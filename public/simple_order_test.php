<?php
// Este script es una versión simplificada para probar específicamente la creación de órdenes
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
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

// Habilitar el modo de depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Función para mostrar mensajes
function showMessage($message, $type = 'info') {
    $class = 'alert-info';
    if ($type == 'error') $class = 'alert-danger';
    if ($type == 'success') $class = 'alert-success';
    if ($type == 'warning') $class = 'alert-warning';
    
    echo "<div class='alert $class'>$message</div>";
}

// Iniciar la salida HTML
echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Prueba Simple de Órdenes</title>
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
    </style>
</head>
<body>
    <div class='container py-5'>
        <h1>Prueba Simple de Órdenes</h1>";

// Verificar si se ha enviado el formulario para crear una orden
if (isset($_POST['create_order'])) {
    try {
        // Iniciar transacción
        DB::beginTransaction();
        
        // Crear una nueva orden usando el modelo Eloquent
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
        $order->notes = 'Orden de prueba';
        
        // Guardar la orden
        $saved = $order->save();
        
        if (!$saved) {
            throw new \Exception("No se pudo guardar la orden");
        }
        
        showMessage("Orden creada con éxito. ID: {$order->id}", 'success');
        
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
        
        showMessage("Item de orden creado con éxito", 'success');
        
        // Confirmar transacción
        DB::commit();
        
        // Mostrar detalles de la orden
        echo "<h5>Detalles de la orden:</h5>";
        echo "<pre>" . print_r($order->toArray(), true) . "</pre>";
        
        // Mostrar detalles del item
        echo "<h5>Detalles del item:</h5>";
        echo "<pre>" . print_r($orderItem->toArray(), true) . "</pre>";
        
    } catch (\Exception $e) {
        // Revertir transacción en caso de error
        DB::rollBack();
        
        showMessage("Error al crear la orden: " . $e->getMessage(), 'error');
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Verificar si se ha enviado el formulario para crear una orden con Query Builder
if (isset($_POST['create_order_qb'])) {
    try {
        // Generar datos para la inserción
        $sessionId = Str::uuid()->toString();
        $now = now();
        
        // Intentar insertar directamente en la tabla orders usando Query Builder
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => null,
            'session_id' => $sessionId,
            'total' => 100.00,
            'status' => 'pending',
            'shipping_address' => 'Dirección de prueba',
            'payment_method' => 'cash',
            'name' => 'Usuario de Prueba QB',
            'email' => 'test_qb@example.com',
            'phone' => '123456789',
            'notes' => 'Orden de prueba con Query Builder',
            'created_at' => $now,
            'updated_at' => $now
        ]);
        
        showMessage("Orden insertada correctamente con ID: $orderId", 'success');
        
        // Verificar que la orden se haya insertado
        $order = DB::table('orders')->find($orderId);
        
        echo "<h5>Datos de la orden insertada:</h5>";
        echo "<pre>" . print_r($order, true) . "</pre>";
        
        // Obtener un producto para la orden
        $product = DB::table('products')->first();
        
        if (!$product) {
            throw new \Exception("No hay productos disponibles");
        }
        
        // Insertar un item de orden
        $orderItemId = DB::table('order_items')->insertGetId([
            'order_id' => $orderId,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
            'created_at' => $now,
            'updated_at' => $now
        ]);
        
        showMessage("Item de orden insertado correctamente con ID: $orderItemId", 'success');
        
        // Verificar que el item se haya insertado
        $orderItem = DB::table('order_items')->find($orderItemId);
        
        echo "<h5>Datos del item insertado:</h5>";
        echo "<pre>" . print_r($orderItem, true) . "</pre>";
        
    } catch (\Exception $e) {
        showMessage("Error al insertar en la tabla orders: " . $e->getMessage(), 'error');
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Verificar si se ha enviado el formulario para verificar las órdenes existentes
if (isset($_POST['check_orders'])) {
    try {
        // Obtener todas las órdenes
        $orders = DB::table('orders')->get();
        
        echo "<h5>Órdenes existentes (" . count($orders) . "):</h5>";
        
        if (count($orders) > 0) {
            echo "<pre>" . print_r($orders->toArray(), true) . "</pre>";
        } else {
            showMessage("No hay órdenes en la base de datos", 'warning');
        }
        
    } catch (\Exception $e) {
        showMessage("Error al verificar órdenes: " . $e->getMessage(), 'error');
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Verificar si se ha enviado el formulario para verificar la estructura de la tabla orders
if (isset($_POST['check_structure'])) {
    try {
        // Verificar si la tabla existe
        $tableExists = DB::select("SHOW TABLES LIKE 'orders'");
        
        if (count($tableExists) > 0) {
            // Obtener la estructura de la tabla
            $columns = DB::select("SHOW COLUMNS FROM orders");
            
            echo "<h5>Estructura de la tabla orders:</h5>";
            echo "<pre>" . print_r($columns, true) . "</pre>";
            
            // Verificar el motor de almacenamiento
            $tableStatus = DB::select("SHOW TABLE STATUS LIKE 'orders'");
            
            echo "<h5>Estado de la tabla orders:</h5>";
            echo "<pre>" . print_r($tableStatus, true) . "</pre>";
        } else {
            showMessage("La tabla orders no existe", 'error');
        }
        
    } catch (\Exception $e) {
        showMessage("Error al verificar estructura: " . $e->getMessage(), 'error');
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Verificar si se ha enviado el formulario para ejecutar una consulta SQL personalizada
if (isset($_POST['execute_query']) && !empty($_POST['sql_query'])) {
    try {
        $sql = $_POST['sql_query'];
        $results = DB::select($sql);
        
        echo "<h5>Resultados de la consulta:</h5>";
        echo "<pre>" . print_r($results, true) . "</pre>";
        
    } catch (\Exception $e) {
        showMessage("Error al ejecutar consulta: " . $e->getMessage(), 'error');
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Mostrar formulario para crear una orden con Eloquent
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Crear Orden con Eloquent</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <p>Esta acción intentará crear una orden usando el modelo Eloquent.</p>
            <input type='hidden' name='create_order' value='1'>
            <button type='submit' class='btn btn-success'>Crear Orden</button>
        </form>
    </div>
</div>";

// Mostrar formulario para crear una orden con Query Builder
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Crear Orden con Query Builder</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <p>Esta acción intentará crear una orden usando Query Builder.</p>
            <input type='hidden' name='create_order_qb' value='1'>
            <button type='submit' class='btn btn-success'>Crear Orden con QB</button>
        </form>
    </div>
</div>";

// Mostrar formulario para verificar órdenes existentes
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Verificar Órdenes Existentes</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <p>Esta acción verificará las órdenes existentes en la base de datos.</p>
            <input type='hidden' name='check_orders' value='1'>
            <button type='submit' class='btn btn-success'>Verificar Órdenes</button>
        </form>
    </div>
</div>";

// Mostrar formulario para verificar la estructura de la tabla orders
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Verificar Estructura de la Tabla</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <p>Esta acción verificará la estructura de la tabla orders.</p>
            <input type='hidden' name='check_structure' value='1'>
            <button type='submit' class='btn btn-success'>Verificar Estructura</button>
        </form>
    </div>
</div>";

// Mostrar formulario para ejecutar una consulta SQL personalizada
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Ejecutar Consulta SQL</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <div class='mb-3'>
                <label for='sql_query' class='form-label'>Consulta SQL:</label>
                <textarea class='form-control' id='sql_query' name='sql_query' rows='3' required></textarea>
                <div class='form-text'>Ejemplo: SELECT * FROM orders LIMIT 10</div>
            </div>
            <input type='hidden' name='execute_query' value='1'>
            <button type='submit' class='btn btn-success'>Ejecutar Consulta</button>
        </form>
    </div>
</div>";

// Finalizar la salida HTML
echo "</div>
</body>
</html>";
?>

