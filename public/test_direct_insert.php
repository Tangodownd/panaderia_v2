<?php
// Este script intenta insertar directamente en las tablas orders y carts
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

// Iniciar la salida HTML
echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Prueba de Inserción Directa</title>
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
        <h1>Prueba de Inserción Directa</h1>";

// Verificar si se ha enviado el formulario para insertar en la tabla orders
if (isset($_POST['insert_order'])) {
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
            'name' => 'Usuario de Prueba',
            'email' => 'test@example.com',
            'phone' => '123456789',
            'notes' => 'Orden de prueba creada desde test_direct_insert.php',
            'created_at' => $now,
            'updated_at' => $now
        ]);
        
        showMessage("Orden insertada correctamente con ID: $orderId", 'success');
        
        // Verificar que la orden se haya insertado
        $order = DB::table('orders')->find($orderId);
        
        echo "<h5>Datos de la orden insertada:</h5>";
        echo "<pre>" . print_r($order, true) . "</pre>";
        
    } catch (\Exception $e) {
        showMessage("Error al insertar en la tabla orders: " . $e->getMessage(), 'error');
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Verificar si se ha enviado el formulario para insertar en la tabla carts
if (isset($_POST['insert_cart'])) {
    try {
        // Generar datos para la inserción
        $sessionId = Str::uuid()->toString();
        $now = now();
        
        // Intentar insertar directamente en la tabla carts usando Query Builder
        $cartId = DB::table('carts')->insertGetId([
            'user_id' => null,
            'session_id' => $sessionId,
            'total' => 0,
            'created_at' => $now,
            'updated_at' => $now
        ]);
        
        showMessage("Carrito insertado correctamente con ID: $cartId", 'success');
        
        // Verificar que el carrito se haya insertado
        $cart = DB::table('carts')->find($cartId);
        
        echo "<h5>Datos del carrito insertado:</h5>";
        echo "<pre>" . print_r($cart, true) . "</pre>";
        
    } catch (\Exception $e) {
        showMessage("Error al insertar en la tabla carts: " . $e->getMessage(), 'error');
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Verificar si se ha enviado el formulario para insertar en la tabla cart_items
if (isset($_POST['insert_cart_item']) && !empty($_POST['cart_id'])) {
    try {
        $cartId = $_POST['cart_id'];
        $now = now();
        
        // Obtener un producto para el carrito
        $product = DB::table('products')->first();
        
        if (!$product) {
            throw new \Exception("No hay productos disponibles");
        }
        
        // Intentar insertar directamente en la tabla cart_items usando Query Builder
        $cartItemId = DB::table('cart_items')->insertGetId([
            'cart_id' => $cartId,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
            'created_at' => $now,
            'updated_at' => $now
        ]);
        
        showMessage("Item de carrito insertado correctamente con ID: $cartItemId", 'success');
        
        // Verificar que el item se haya insertado
        $cartItem = DB::table('cart_items')->find($cartItemId);
        
        echo "<h5>Datos del item insertado:</h5>";
        echo "<pre>" . print_r($cartItem, true) . "</pre>";
        
        // Actualizar el total del carrito
        DB::table('carts')->where('id', $cartId)->update([
            'total' => DB::raw("total + {$product->price}"),
            'updated_at' => $now
        ]);
        
        showMessage("Total del carrito actualizado", 'success');
        
    } catch (\Exception $e) {
        showMessage("Error al insertar en la tabla cart_items: " . $e->getMessage(), 'error');
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Verificar si se ha enviado el formulario para insertar en la tabla order_items
if (isset($_POST['insert_order_item']) && !empty($_POST['order_id'])) {
    try {
        $orderId = $_POST['order_id'];
        $now = now();
        
        // Obtener un producto para la orden
        $product = DB::table('products')->first();
        
        if (!$product) {
            throw new \Exception("No hay productos disponibles");
        }
        
        // Intentar insertar directamente en la tabla order_items usando Query Builder
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
        showMessage("Error al insertar en la tabla order_items: " . $e->getMessage(), 'error');
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Mostrar formulario para insertar en la tabla orders
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Insertar en la tabla orders</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <p>Esta acción intentará insertar un registro directamente en la tabla orders.</p>
            <input type='hidden' name='insert_order' value='1'>
            <button type='submit' class='btn btn-success'>Insertar en orders</button>
        </form>
    </div>
</div>";

// Mostrar formulario para insertar en la tabla carts
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Insertar en la tabla carts</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <p>Esta acción intentará insertar un registro directamente en la tabla carts.</p>
            <input type='hidden' name='insert_cart' value='1'>
            <button type='submit' class='btn btn-success'>Insertar en carts</button>
        </form>
    </div>
</div>";

// Mostrar formulario para insertar en la tabla cart_items
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Insertar en la tabla cart_items</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <div class='mb-3'>
                <label for='cart_id' class='form-label'>ID del carrito:</label>
                <input type='number' class='form-control' id='cart_id' name='cart_id' required>
            </div>
            <input type='hidden' name='insert_cart_item' value='1'>
            <button type='submit' class='btn btn-success'>Insertar en cart_items</button>
        </form>
    </div>
</div>";

// Mostrar formulario para insertar en la tabla order_items
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Insertar en la tabla order_items</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <div class='mb-3'>
                <label for='order_id' class='form-label'>ID de la orden:</label>
                <input type='number' class='form-control' id='order_id' name='order_id' required>
            </div>
            <input type='hidden' name='insert_order_item' value='1'>
            <button type='submit' class='btn btn-success'>Insertar en order_items</button>
        </form>
    </div>
</div>";

// Mostrar los últimos registros de las tablas
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Últimos registros</h5>
    </div>
    <div class='card-body'>";

// Mostrar últimos registros de orders
$orders = DB::table('orders')->orderBy('id', 'desc')->limit(5)->get();
echo "<h5>Últimos registros de orders:</h5>";
if (count($orders) > 0) {
    echo "<pre>" . print_r($orders, true) . "</pre>";
} else {
    showMessage("No hay registros en la tabla orders", 'warning');
}

// Mostrar últimos registros de carts
$carts = DB::table('carts')->orderBy('id', 'desc')->limit(5)->get();
echo "<h5>Últimos registros de carts:</h5>";
if (count($carts) > 0) {
    echo "<pre>" . print_r($carts, true) . "</pre>";
} else {
    showMessage("No hay registros en la tabla carts", 'warning');
}

echo "</div></div>";

// Finalizar la salida HTML
echo "</div>
</body>
</html>";
?>

