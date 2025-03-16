<?php
// Este script repara la sesión del carrito y crea uno nuevo si es necesario
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Reparación de Sesión del Carrito</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px; }
        button:hover { background: #45a049; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Reparación de Sesión del Carrito</h1>";

// Verificar cookie de sesión
$sessionId = $_COOKIE['cart_session_id'] ?? null;

echo "<div class='card'>";
echo "<h2>Estado Actual</h2>";

if ($sessionId) {
    echo "<p>Cookie de sesión actual: <code>$sessionId</code></p>";
    
    // Verificar si existe un carrito con esta sesión
    $cart = Cart::where('session_id', $sessionId)->first();
    
    if ($cart) {
        echo "<p class='success'>Carrito encontrado con ID: {$cart->id}</p>";
        
        // Verificar items del carrito
        $items = CartItem::where('cart_id', $cart->id)->count();
        echo "<p>El carrito tiene {$items} items</p>";
    } else {
        echo "<p class='warning'>No se encontró un carrito con la sesión actual</p>";
    }
} else {
    echo "<p class='error'>No se encontró la cookie de sesión 'cart_session_id'</p>";
}

echo "</div>";

// Acciones de reparación
if (isset($_POST['action'])) {
    echo "<div class='card'>";
    echo "<h2>Resultado de la Acción</h2>";
    
    if ($_POST['action'] === 'create_new_session') {
        // Generar un nuevo ID de sesión
        $newSessionId = Str::uuid()->toString();
        
        // Establecer la cookie
        setcookie('cart_session_id', $newSessionId, time() + (86400 * 30), '/');
        
        echo "<p class='success'>Nueva sesión creada: <code>$newSessionId</code></p>";
        echo "<p>La cookie ha sido actualizada. Recarga la página para ver los cambios.</p>";
    }
    else if ($_POST['action'] === 'create_new_cart') {
        // Verificar si hay una sesión
        if (!$sessionId) {
            $sessionId = Str::uuid()->toString();
            setcookie('cart_session_id', $sessionId, time() + (86400 * 30), '/');
            echo "<p class='success'>Nueva sesión creada: <code>$sessionId</code></p>";
        }
        
        // Verificar si ya existe un carrito con esta sesión
        $existingCart = Cart::where('session_id', $sessionId)->first();
        
        if ($existingCart) {
            echo "<p class='warning'>Ya existe un carrito con esta sesión (ID: {$existingCart->id})</p>";
        } else {
            // Crear un nuevo carrito
            $cart = new Cart();
            $cart->session_id = $sessionId;
            $cart->user_id = null;
            $cart->total = 0;
            $cart->save();
            
            echo "<p class='success'>Nuevo carrito creado con ID: {$cart->id}</p>";
            Log::info('Carrito creado manualmente con session_id: ' . $sessionId);
        }
    }
    else if ($_POST['action'] === 'force_fix') {
        // Eliminar todos los carritos existentes
        $deletedCarts = Cart::truncate();
        
        // Eliminar todos los items de carrito
        $deletedItems = CartItem::truncate();
        
        // Generar un nuevo ID de sesión
        $newSessionId = Str::uuid()->toString();
        
        // Establecer la cookie
        setcookie('cart_session_id', $newSessionId, time() + (86400 * 30), '/');
        
        // Crear un nuevo carrito
        $cart = new Cart();
        $cart->session_id = $newSessionId;
        $cart->user_id = null;
        $cart->total = 0;
        $cart->save();
        
        echo "<p class='success'>Reparación forzada completada:</p>";
        echo "<ul>";
        echo "<li>Todos los carritos anteriores han sido eliminados</li>";
        echo "<li>Todos los items de carrito han sido eliminados</li>";
        echo "<li>Nueva sesión creada: <code>$newSessionId</code></li>";
        echo "<li>Nuevo carrito creado con ID: {$cart->id}</li>";
        echo "</ul>";
        
        Log::info('Reparación forzada completada. Nuevo carrito creado con session_id: ' . $newSessionId);
    }
    
    echo "</div>";
}

// Formulario de acciones
echo "<div class='card'>";
echo "<h2>Acciones de Reparación</h2>";

echo "<form method='post' style='margin-bottom: 10px;'>";
echo "<input type='hidden' name='action' value='create_new_session'>";
echo "<button type='submit'>Crear Nueva Sesión</button>";
echo "<span>Crea una nueva cookie de sesión sin afectar la base de datos</span>";
echo "</form>";

echo "<form method='post' style='margin-bottom: 10px;'>";
echo "<input type='hidden' name='action' value='create_new_cart'>";
echo "<button type='submit'>Crear Nuevo Carrito</button>";
echo "<span>Crea un nuevo carrito con la sesión actual</span>";
echo "</form>";

echo "<form method='post' style='margin-bottom: 10px;'>";
echo "<input type='hidden' name='action' value='force_fix'>";
echo "<button type='submit' style='background-color: #f44336;'>Reparación Forzada</button>";
echo "<span>ADVERTENCIA: Elimina todos los carritos existentes y crea uno nuevo</span>";
echo "</form>";

echo "</div>";

// Enlaces útiles
echo "<div class='card'>";
echo "<h2>Enlaces Útiles</h2>";
echo "<a href='/Panaderia_v2/public/check_session.php'><button>Verificar Sesión</button></a>";
echo "<a href='/Panaderia_v2/public/'><button>Ir a la Tienda</button></a>";
echo "</div>";

echo "</div></body></html>";
?>