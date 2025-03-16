<?php
// Incluir el autoloader de Composer
require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configurar el buffer de salida para evitar problemas de "headers already sent"
ob_start();

// Conectar a la base de datos
try {
    $db = new PDO(
        "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_DATABASE'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

// Función para generar un UUID
function generateUuid() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Función para obtener o crear un carrito
function getOrCreateCart($db, $sessionId = null, $userId = null) {
    if (!$sessionId) {
        $sessionId = generateUuid();
        // Establecer cookie de sesión
        setcookie('cart_session_id', $sessionId, time() + 60*60*24*30, '/');
    }
    
    // Buscar carrito por session_id o user_id
    $query = "SELECT * FROM carts WHERE ";
    $params = [];
    
    if ($userId) {
        $query .= "user_id = ?";
        $params[] = $userId;
    } else {
        $query .= "session_id = ?";
        $params[] = $sessionId;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si no existe, crear uno nuevo
    if (!$cart) {
        $stmt = $db->prepare("INSERT INTO carts (user_id, session_id, total, created_at, updated_at) VALUES (?, ?, 0, NOW(), NOW())");
        $stmt->execute([$userId, $sessionId]);
        
        $cartId = $db->lastInsertId();
        
        $stmt = $db->prepare("SELECT * FROM carts WHERE id = ?");
        $stmt->execute([$cartId]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return $cart;
}

// Función para añadir un producto al carrito
function addToCart($db, $cartId, $productId, $quantity) {
    // Verificar si el producto ya está en el carrito
    $stmt = $db->prepare("SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->execute([$cartId, $productId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener el precio del producto
    $stmt = $db->prepare("SELECT price FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    $price = $product['price'];
    
    if ($item) {
        // Actualizar cantidad si ya existe
        $stmt = $db->prepare("UPDATE cart_items SET quantity = quantity + ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$quantity, $item['id']]);
    } else {
        // Crear nuevo item
        $stmt = $db->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, price, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$cartId, $productId, $quantity, $price]);
    }
    
    // Recalcular total
    $stmt = $db->prepare("SELECT SUM(quantity * price) as total FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cartId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $result['total'] ?: 0;
    
    // Actualizar total en el carrito
    $stmt = $db->prepare("UPDATE carts SET total = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$total, $cartId]);
    
    return $total;
}

// Función para obtener los items del carrito
function getCartItems($db, $cartId) {
    $stmt = $db->prepare("
        SELECT ci.*, p.name, p.image, p.description 
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = ?
    ");
    $stmt->execute([$cartId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Verificar si se está ejecutando una acción
$action = $_GET['action'] ?? 'view';
$result = null;
$error = null;

// Ejecutar la acción seleccionada
switch ($action) {
    case 'get_cart':
        // Obtener el carrito actual
        $sessionId = $_COOKIE['cart_session_id'] ?? null;
        $userId = 1; // Simular usuario autenticado
        
        try {
            $cart = getOrCreateCart($db, $sessionId, $userId);
            $items = getCartItems($db, $cart['id']);
            
            $result = [
                'cart' => $cart,
                'items' => $items
            ];
            
            // Establecer cookie de sesión si no existe
            if (!$sessionId) {
                $sessionId = $cart['session_id'];
                setcookie('cart_session_id', $sessionId, time() + 60*60*24*30, '/');
                echo "<p>Se ha establecido una nueva cookie de sesión: $sessionId</p>";
            }
        } catch (Exception $e) {
            $error = "Error al obtener el carrito: " . $e->getMessage();
        }
        break;
        
    case 'add_product':
        // Mostrar formulario para añadir producto
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // No hacer nada, se mostrará el formulario
        } else {
            // Procesar formulario
            $productId = $_POST['product_id'] ?? null;
            $quantity = $_POST['quantity'] ?? 1;
            
            if (!$productId) {
                $error = "Error: No se ha seleccionado un producto";
                break;
            }
            
            try {
                // Obtener el carrito actual
                $sessionId = $_COOKIE['cart_session_id'] ?? null;
                $userId = 1; // Simular usuario autenticado
                
                $cart = getOrCreateCart($db, $sessionId, $userId);
                
                // Añadir producto al carrito
                $total = addToCart($db, $cart['id'], $productId, $quantity);
                
                // Obtener los items actualizados
                $items = getCartItems($db, $cart['id']);
                
                $result = [
                    'success' => true,
                    'message' => 'Producto añadido al carrito',
                    'cart' => $cart,
                    'items' => $items,
                    'total' => $total
                ];
                
                // Establecer cookie de sesión si no existe
                if (!$sessionId) {
                    $sessionId = $cart['session_id'];
                    setcookie('cart_session_id', $sessionId, time() + 60*60*24*30, '/');
                    echo "<p>Se ha establecido una nueva cookie de sesión: $sessionId</p>";
                }
            } catch (Exception $e) {
                $error = "Error al añadir producto al carrito: " . $e->getMessage();
            }
        }
        break;
        
    case 'clear_cart':
        // Vaciar el carrito
        $sessionId = $_COOKIE['cart_session_id'] ?? null;
        $userId = 1; // Simular usuario autenticado
        
        try {
            $cart = getOrCreateCart($db, $sessionId, $userId);
            
            // Eliminar todos los items
            $stmt = $db->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmt->execute([$cart['id']]);
            
            // Actualizar el total en el carrito
            $stmt = $db->prepare("UPDATE carts SET total = 0, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$cart['id']]);
            
            $result = [
                'success' => true,
                'message' => 'Carrito vaciado correctamente',
                'cart' => $cart,
                'items' => []
            ];
        } catch (Exception $e) {
            $error = "Error al vaciar el carrito: " . $e->getMessage();
        }
        break;
}

// Obtener lista de productos para el formulario
$products = [];
if ($dbConnected) {
    try {
        $stmt = $db->query("SELECT id, name, price FROM products ORDER BY name");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Ignorar errores
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Depuración de API del Carrito</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1, h2, h3 {
            color: #8B4513;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        form {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        button {
            background-color: #8B4513;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #704214;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Depuración de API del Carrito</h1>
        
        <?php if (!$dbConnected): ?>
            <div class="alert alert-danger">
                <p><strong>Error de conexión a la base de datos:</strong> <?php echo $dbError; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Acciones disponibles</h2>
            <ul>
                <li><a href="?action=get_cart">Obtener carrito</a></li>
                <li><a href="?action=add_product">Añadir producto al carrito</a></li>
                <li><a href="?action=clear_cart">Vaciar carrito</a></li>
            </ul>
        </div>
        
        <?php if ($action === 'get_cart'): ?>
            <div class="card">
                <h2>Carrito actual</h2>
                <?php if ($result): ?>
                    <h3>Información del carrito</h3>
                    <pre><?php print_r($result['cart']); ?></pre>
                    
                    <h3>Items del carrito</h3>
                    <?php if (count($result['items']) > 0): ?>
                        <table>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Subtotal</th>
                            </tr>
                            
                            <?php 
                            $total = 0;
                            foreach ($result['items'] as $item): 
                                $subtotal = $item['quantity'] * $item['price'];
                                $total += $subtotal;
                            ?>
                                <tr>
                                    <td><?php echo $item['name']; ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo $item['price']; ?></td>
                                    <td>$<?php echo $subtotal; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <tr>
                                <td colspan="3" align="right"><strong>Total:</strong></td>
                                <td>$<?php echo $total; ?></td>
                            </tr>
                        </table>
                    <?php else: ?>
                        <p>El carrito está vacío</p>
                    <?php endif; ?>
                    
                    <h3>Cookies</h3>
                    <pre><?php print_r($_COOKIE); ?></pre>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'add_product'): ?>
            <div class="card">
                <h2>Añadir producto al carrito</h2>
                
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $result): ?>
                    <div class="alert alert-success">
                        <p><?php echo $result['message']; ?></p>
                    </div>
                    
                    <h3>Carrito actualizado</h3>
                    <pre><?php print_r($result['cart']); ?></pre>
                    
                    <h3>Items del carrito</h3>
                    <?php if (count($result['items']) > 0): ?>
                        <table>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Subtotal</th>
                            </tr>
                            
                            <?php 
                            $total = 0;
                            foreach ($result['items'] as $item): 
                                $subtotal = $item['quantity'] * $item['price'];
                                $total += $subtotal;
                            ?>
                                <tr>
                                    <td><?php echo $item['name']; ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo $item['price']; ?></td>
                                    <td>$<?php echo $subtotal; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <tr>
                                <td colspan="3" align="right"><strong>Total:</strong></td>
                                <td>$<?php echo $total; ?></td>
                            </tr>
                        </table>
                    <?php else: ?>
                        <p>El carrito está vacío</p>
                    <?php endif; ?>
                <?php else: ?>
                    <form method="post" action="?action=add_product">
                        <div>
                            <label for="product_id">Producto:</label>
                            <select name="product_id" id="product_id" required>
                                <option value="">Seleccionar producto</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>">
                                        <?php echo $product['name']; ?> - $<?php echo $product['price']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="quantity">Cantidad:</label>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" required>
                        </div>
                        <button type="submit">Añadir al carrito</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'clear_cart'): ?>
            <div class="card">
                <h2>Vaciar carrito</h2>
                
                <?php if ($result): ?>
                    <div class="alert alert-success">
                        <p><?php echo $result['message']; ?></p>
                    </div>
                    
                    <p>El carrito ha sido vaciado correctamente.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Información de depuración</h2>
            
            <h3>Cookies actuales</h3>
            <pre><?php print_r($_COOKIE); ?></pre>
            
            <h3>Solución al error "Headers already sent"</h3>
            <div class="alert alert-info">
                <p>El error "Cannot modify header information - headers already sent" ocurre cuando intentas establecer cookies o modificar encabezados HTTP después de que ya se ha enviado contenido al navegador.</p>
                <p>Soluciones:</p>
                <ol>
                    <li>Asegúrate de que todas las llamadas a <code>setcookie()</code>, <code>header()</code>, <code>session_start()</code>, etc. se realicen antes de cualquier salida HTML o texto.</li>
                    <li>Usa <code>ob_start()</code> al principio del script para almacenar en búfer toda la salida.</li>
                    <li>Verifica que no haya espacios o líneas en blanco antes de <code>&lt;?php</code>.</li>
                    <li>Asegúrate de que los archivos estén guardados sin BOM (Byte Order Mark) UTF-8.</li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// Liberar el buffer de salida
ob_end_flush();
?>
