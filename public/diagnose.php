<?php

// Este script debe colocarse en la raíz del proyecto público

// Cargar el framework de Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Inicializar variables
$cartData = [];
$orderData = [];
$cartItems = [];
$cartSession = '';
$cookieError = false;

// Obtener sesión del carrito de la cookie
if (isset($_COOKIE['cart_session_id'])) {
    $cartSession = $_COOKIE['cart_session_id'];
} else {
    $cookieError = true;
}

// Consultas a la base de datos
try {
    // Consultar carrito actual
    if (!empty($cartSession)) {
        $cart = DB::table('carts')->where('session_id', $cartSession)->first();
        if ($cart) {
            $cartData = (array) $cart;
            $cartItems = DB::table('cart_items')
                ->join('products', 'cart_items.product_id', '=', 'products.id')
                ->where('cart_id', $cart->id)
                ->select('cart_items.*', 'products.name as product_name')
                ->get()
                ->toArray();
        }
    }
    
    // Consultar últimos pedidos
    $orders = DB::table('orders')->orderBy('id', 'desc')->limit(5)->get();
    $orderData = $orders->toArray();
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Funciones para mostrar datos
function formatJson($data) {
    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function highlightJson($json) {
    $result = '';
    $level = 0;
    $in_quotes = false;
    $in_escape = false;
    $ends_line_level = NULL;
    $json_length = strlen($json);

    for($i = 0; $i < $json_length; $i++) {
        $char = $json[$i];
        $new_line_level = NULL;
        $post = "";
        if($ends_line_level !== NULL) {
            $new_line_level = $ends_line_level;
            $ends_line_level = NULL;
        }
        if($in_escape) {
            $in_escape = false;
        } else if($char === '"') {
            $in_quotes = !$in_quotes;
        } else if(!$in_quotes) {
            switch($char) {
                case '}': case ']':
                    $level--;
                    $ends_line_level = NULL;
                    $new_line_level = $level;
                    break;
                case '{': case '[':
                    $level++;
                case ',':
                    $ends_line_level = $level;
                    break;
                case ':':
                    $post = " ";
                    break;
                case " ": case "\t": case "\n": case "\r":
                    $char = "";
                    $ends_line_level = $new_line_level;
                    $new_line_level = NULL;
                    break;
            }
        } else if($char === '\\') {
            $in_escape = true;
        }
        if($new_line_level !== NULL) {
            $result .= "\n".str_repeat("  ", $new_line_level);
        }
        $result .= $char.$post;
    }

    return $result;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diagnóstico del sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            max-height: 400px;
            overflow-y: auto;
        }
        .json-string { color: green; }
        .json-number { color: blue; }
        .json-boolean { color: purple; }
        .json-null { color: red; }
        .json-key { color: #c92c2c; }
        .card {
            margin-bottom: 20px;
        }
        }
        .json-key { color: #c92c2c; }
        .card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Diagnóstico del Sistema de Pedidos</h1>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Estado de Cookies</h5>
            </div>
            <div class="card-body">
                <?php if ($cookieError): ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> No se encontró la cookie 'cart_session_id'. Esto puede causar problemas con el carrito.
                    </div>
                    <p>Para corregir este problema, ejecuta este código en la consola del navegador:</p>
                    <pre>document.cookie = "cart_session_id=<?php echo uniqid(); ?>; path=/; max-age=2592000";</pre>
                <?php else: ?>
                    <div class="alert alert-success">
                        <strong>Correcto:</strong> La cookie 'cart_session_id' está presente.
                    </div>
                    <p>Valor de la cookie: <code><?php echo htmlspecialchars($cartSession); ?></code></p>
                <?php endif; ?>
                <h6 class="mt-3">Todas las cookies:</h6>
                <pre><?php print_r($_COOKIE); ?></pre>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Estado del Carrito</h5>
            </div>
            <div class="card-body">
                <?php if (empty($cartData)): ?>
                    <div class="alert alert-warning">
                        <strong>Aviso:</strong> No se encontró un carrito activo con la sesión actual.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <strong>Info:</strong> Carrito encontrado con ID: <?php echo $cartData['id']; ?>
                    </div>
                    <h6>Datos del carrito:</h6>
                    <pre><?php echo highlightJson(formatJson($cartData)); ?></pre>
                    
                    <h6 class="mt-3">Items en el carrito (<?php echo count($cartItems); ?>):</h6>
                    <?php if (empty($cartItems)): ?>
                        <div class="alert alert-warning">El carrito está vacío.</div>
                    <?php else: ?>
                        <pre><?php echo highlightJson(formatJson($cartItems)); ?></pre>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Últimos Pedidos</h5>
            </div>
            <div class="card-body">
                <?php if (empty($orderData)): ?>
                    <div class="alert alert-warning">
                        <strong>Aviso:</strong> No se encontraron pedidos en la base de datos.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <strong>Info:</strong> Se encontraron <?php echo count($orderData); ?> pedidos.
                    </div>
                    <pre><?php echo highlightJson(formatJson($orderData)); ?></pre>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Acciones de Diagnóstico</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-grid">
                            <button class="btn btn-warning mb-3" onclick="resetCartCookie()">Resetear Cookie del Carrito</button>
                        </div>
                        <div class="d-grid">
                            <a href="test_order_simple.php" class="btn btn-success mb-3">Probar Creación de Pedido</a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-grid">
                            <button class="btn btn-danger mb-3" onclick="clearCart()">Vaciar Carrito Actual</button>
                        </div>
                        <div class="d-grid">
                            <a href="/" class="btn btn-primary">Volver a la Tienda</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function resetCartCookie() {
            const newSessionId = 'cart_' + Math.random().toString(36).substring(2, 15);
            document.cookie = `cart_session_id=${newSessionId}; path=/; max-age=2592000`;
            alert('Cookie del carrito restablecida. Recarga la página para ver los cambios.');
            location.reload();
        }
        
        function clearCart() {
            if (confirm('¿Estás seguro de que quieres vaciar el carrito actual?')) {
                fetch('/api/cart', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    alert('Carrito vaciado correctamente');
                    location.reload();
                })
                .catch(error => {
                    alert('Error al vaciar el carrito: ' + error);
                });
            }
        }
    </script>
</body>
</html>

