<?php
// Este script verifica el estado de las cookies y sesiones
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use Illuminate\Support\Str;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Verificación de Sesión</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #45a049; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Verificación de Sesión del Carrito</h1>";

// Verificar cookie de sesión
$sessionId = $_COOKIE['cart_session_id'] ?? null;

echo "<div class='card'>";
echo "<h2>Estado de la Cookie</h2>";

if ($sessionId) {
    echo "<p class='success'>Cookie de sesión encontrada: <code>$sessionId</code></p>";
} else {
    echo "<p class='error'>No se encontró la cookie de sesión 'cart_session_id'</p>";
}

echo "</div>";

// Verificar carrito en la base de datos
echo "<div class='card'>";
echo "<h2>Estado del Carrito</h2>";

if ($sessionId) {
    $cart = Cart::where('session_id', $sessionId)->first();
    
    if ($cart) {
        echo "<p class='success'>Carrito encontrado en la base de datos con ID: {$cart->id}</p>";
        echo "<pre>" . print_r($cart->toArray(), true) . "</pre>";
        
        // Verificar items del carrito
        $items = DB::table('cart_items')
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->where('cart_id', $cart->id)
            ->select('cart_items.*', 'products.name as product_name')
            ->get();
        
        echo "<h3>Items del carrito (" . count($items) . ")</h3>";
        
        if (count($items) > 0) {
            echo "<pre>" . print_r($items->toArray(), true) . "</pre>";
        } else {
            echo "<p class='warning'>El carrito está vacío</p>";
        }
    } else {
        echo "<p class='warning'>No se encontró un carrito con la sesión actual</p>";
    }
} else {
    echo "<p class='error'>No se puede verificar el carrito sin una cookie de sesión</p>";
}

echo "</div>";

// Botones de acción
echo "<div class='card'>";
echo "<h2>Acciones</h2>";
echo "<button onclick='createNewSession()'>Crear Nueva Sesión</button> ";
echo "<button onclick='location.reload()'>Actualizar Página</button> ";
echo "<button onclick='window.location.href=\"/Panaderia_v2/public/\"'>Ir a la Tienda</button>";
echo "</div>";

// Script para crear nueva sesión
echo "<script>
function createNewSession() {
    const newSessionId = 'cart_' + Math.random().toString(36).substring(2, 15);
    document.cookie = 'cart_session_id=' + newSessionId + '; path=/; max-age=2592000';
    alert('Nueva sesión creada: ' + newSessionId);
    location.reload();
}
</script>";

echo "</div></body></html>";
?>