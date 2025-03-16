<?php
// Incluir el autoloader de Composer
require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

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

// Procesar acciones que requieren establecer cookies
$message = '';
$cookieMessage = '';

// Verificar si se está estableciendo una cookie
if (isset($_POST['set_cookie'])) {
    $cookieName = $_POST['cookie_name'] ?? 'test_cookie';
    $cookieValue = $_POST['cookie_value'] ?? 'test_value';
    $cookieExpiry = time() + (30 * 24 * 60 * 60); // 30 días
    
    // Establecer la cookie
    setcookie($cookieName, $cookieValue, $cookieExpiry, '/');
    $cookieMessage = "Cookie '$cookieName' establecida con valor '$cookieValue' y expiración de 30 días.";
}

// Verificar si se está eliminando una cookie
if (isset($_GET['delete_cookie']) && !empty($_GET['delete_cookie'])) {
    $cookieName = $_GET['delete_cookie'];
    setcookie($cookieName, '', time() - 3600, '/'); // Expirar la cookie
    $cookieMessage = "Cookie '$cookieName' eliminada.";
}

// Verificar si se está estableciendo la cookie de sesión del carrito
if (isset($_POST['set_cart_session'])) {
    $sessionId = $_POST['session_id'] ?? generateUuid();
    setcookie('cart_session_id', $sessionId, time() + (30 * 24 * 60 * 60), '/');
    $cookieMessage = "Cookie 'cart_session_id' establecida con valor '$sessionId' y expiración de 30 días.";
}

// Conectar a la base de datos para operaciones de API
$db = null;
try {
    $db = new PDO(
        "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_DATABASE'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $message = "Error de conexión a la base de datos: " . $e->getMessage();
}

// Función para obtener el carrito actual
function getCart($db, $userId = null, $sessionId = null) {
    if (!$db) return null;
    
    $query = "SELECT * FROM carts WHERE ";
    $params = [];
    
    if ($userId) {
        $query .= "user_id = ?";
        $params[] = $userId;
    } else if ($sessionId) {
        $query .= "session_id = ?";
        $params[] = $sessionId;
    } else {
        return null;
    }
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// Función para obtener los items del carrito
function getCartItems($db, $cartId) {
    if (!$db || !$cartId) return [];
    
    try {
        $stmt = $db->prepare("
            SELECT ci.*, p.name, p.price as product_price, p.image 
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.cart_id = ?
        ");
        $stmt->execute([$cartId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Función para añadir un producto al carrito
function addToCart($db, $cartId, $productId, $quantity) {
    if (!$db || !$cartId || !$productId) return false;
    
    try {
        // Verificar si el producto ya está en el carrito
        $stmt = $db->prepare("SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $stmt->execute([$cartId, $productId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener el precio del producto
        $stmt = $db->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return false;
        }
        
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
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Verificar si se está ejecutando una acción de API
$apiAction = $_GET['api_action'] ?? '';
$apiResult = null;

if ($apiAction) {
    $userId = 1; // Simular usuario autenticado
    $sessionId = $_COOKIE['cart_session_id'] ?? null;
    
    switch ($apiAction) {
        case 'get_cart':
            $cart = getCart($db, $userId, $sessionId);
            if ($cart) {
                $items = getCartItems($db, $cart['id']);
                $apiResult = [
                    'success' => true,
                    'cart' => $cart,
                    'items' => $items
                ];
            } else {
                $apiResult = [
                    'success' => false,
                    'message' => 'No se encontró el carrito'
                ];
            }
            break;
            
        case 'add_product':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $productId = $_POST['product_id'] ?? null;
                $quantity = $_POST['quantity'] ?? 1;
                
                if (!$productId) {
                    $apiResult = [
                        'success' => false,
                        'message' => 'No se ha especificado un producto'
                    ];
                    break;
                }
                
                $cart = getCart($db, $userId, $sessionId);
                
                if (!$cart) {
                    // Crear un nuevo carrito
                    $newSessionId = $sessionId ?: generateUuid();
                    $stmt = $db->prepare("INSERT INTO carts (user_id, session_id, total, created_at, updated_at) VALUES (?, ?, 0, NOW(), NOW())");
                    $stmt->execute([$userId, $newSessionId]);
                    
                    $cartId = $db->lastInsertId();
                    
                    // Establecer cookie si no existe
                    if (!$sessionId) {
                        setcookie('cart_session_id', $newSessionId, time() + (30 * 24 * 60 * 60), '/');
                    }
                } else {
                    $cartId = $cart['id'];
                }
                
                $success = addToCart($db, $cartId, $productId, $quantity);
                
                if ($success) {
                    $updatedCart = getCart($db, $userId, $sessionId);
                    $items = getCartItems($db, $updatedCart['id']);
                    
                    $apiResult = [
                        'success' => true,
                        'message' => 'Producto añadido al carrito',
                        'cart' => $updatedCart,
                        'items' => $items
                    ];
                } else {
                    $apiResult = [
                        'success' => false,
                        'message' => 'Error al añadir producto al carrito'
                    ];
                }
            } else {
                $apiResult = [
                    'success' => false,
                    'message' => 'Método no permitido'
                ];
            }
            break;
    }
}

// Obtener lista de productos para el formulario
$products = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT id, name, price FROM products ORDER BY name");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Ignorar errores
    }
}

// Ahora podemos comenzar a enviar la salida HTML
?>
<!DOCTYPE html>
<html>
<head>
    <title>Depuración de Cookies y API del Carrito</title>
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
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab {
            padding: 10px 15px;
            cursor: pointer;
            margin-right: 5px;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            background-color: #f2f2f2;
        }
        .tab.active {
            background-color: #8B4513;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
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
        <h1>Depuración de Cookies y API del Carrito</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-danger">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($cookieMessage)): ?>
            <div class="alert alert-success">
                <?php echo $cookieMessage; ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('cookies')">Cookies</div>
            <div class="tab" onclick="showTab('cart-api')">API del Carrito</div>
            <div class="tab" onclick="showTab('debug-info')">Información de Depuración</div>
        </div>
        
        <div id="cookies" class="tab-content active">
            <div class="card">
                <h2>Cookies Actuales</h2>
                <?php if (empty($_COOKIE)): ?>
                    <p>No hay cookies establecidas.</p>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Nombre</th>
                            <th>Valor</th>
                            <th>Acciones</th>
                        </tr>
                        <?php foreach ($_COOKIE as $name => $value): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($name); ?></td>
                                <td><?php echo htmlspecialchars($value); ?></td>
                                <td>
                                    <a href="?delete_cookie=<?php echo urlencode($name); ?>">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>Establecer Cookie</h2>
                <form method="post" action="">
                    <div>
                        <label for="cookie_name">Nombre de la Cookie:</label>
                        <input type="text" name="cookie_name" id="cookie_name" required>
                    </div>
                    <div>
                        <label for="cookie_value">Valor de la Cookie:</label>
                        <input type="text" name="cookie_value" id="cookie_value" required>
                    </div>
                    <button type="submit" name="set_cookie">Establecer Cookie</button>
                </form>
            </div>
            
            <div class="card">
                <h2>Establecer Cookie de Sesión del Carrito</h2>
                <form method="post" action="">
                    <div>
                        <label for="session_id">ID de Sesión (dejar en blanco para generar uno nuevo):</label>
                        <input type="text" name="session_id" id="session_id" placeholder="UUID de sesión">
                    </div>
                    <button type="submit" name="set_cart_session">Establecer Cookie de Sesión</button>
                </form>
            </div>
        </div>
        
        <div id="cart-api" class="tab-content">
            <div class="card">
                <h2>Obtener Carrito</h2>
                <p>Obtiene el carrito actual basado en la cookie de sesión o el usuario autenticado.</p>
                <form method="get" action="">
                    <input type="hidden" name="api_action" value="get_cart">
                    <button type="submit">Obtener Carrito</button>
                </form>
                
                <?php if ($apiAction === 'get_cart' && $apiResult): ?>
                    <h3>Respuesta:</h3>
                    <pre><?php echo json_encode($apiResult, JSON_PRETTY_PRINT); ?></pre>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>Añadir Producto al Carrito</h2>
                <p>Añade un producto al carrito actual.</p>
                <form method="post" action="?api_action=add_product">
                    <div>
                        <label for="product_id">Producto:</label>
                        <select name="product_id" id="product_id" required>
                            <option value="">Seleccionar producto</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?> - $<?php echo $product['price']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="quantity">Cantidad:</label>
                        <input type="number" name="quantity" id="quantity" value="1" min="1" required>
                    </div>
                    <button type="submit">Añadir al Carrito</button>
                </form>
                
                <?php if ($apiAction === 'add_product' && $apiResult): ?>
                    <h3>Respuesta:</h3>
                    <pre><?php echo json_encode($apiResult, JSON_PRETTY_PRINT); ?></pre>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="debug-info" class="tab-content">
            <div class="card">
                <h2>Información de PHP</h2>
                <table>
                    <tr>
                        <th>Versión de PHP</th>
                        <td><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <th>output_buffering</th>
                        <td><?php echo ini_get('output_buffering') ? 'Activado' : 'Desactivado'; ?></td>
                    </tr>
                    <tr>
                        <th>Directorio de Cookies</th>
                        <td><?php echo ini_get('session.cookie_path'); ?></td>
                    </tr>
                    <tr>
                        <th>Dominio de Cookies</th>
                        <td><?php echo ini_get('session.cookie_domain') ?: 'No establecido'; ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="card">
                <h2>Explicación del Error "Headers already sent"</h2>
                <div class="alert alert-info">
                    <p><strong>Problema:</strong> El error "Cannot modify header information - headers already sent" ocurre cuando intentas establecer cookies o modificar encabezados HTTP después de que ya se ha enviado contenido al navegador.</p>
                    <p><strong>Solución:</strong> Asegúrate de que todas las llamadas a <code>setcookie()</code>, <code>header()</code>, <code>session_start()</code>, etc. se realicen antes de cualquier salida HTML o texto.</p>
                    <p><strong>Causas comunes:</strong></p>
                    <ul>
                        <li>Espacios o líneas en blanco antes de <code>&lt;?php</code></li>
                        <li>Llamadas a <code>echo</code>, <code>print</code> o HTML antes de establecer cookies</li>
                        <li>Archivos guardados con BOM (Byte Order Mark) UTF-8</li>
                        <li>Errores o advertencias PHP que generan salida antes de establecer cookies</li>
                    </ul>
                    <p><strong>Soluciones alternativas:</strong></p>
                    <ul>
                        <li>Usar <code>ob_start()</code> al principio del script para almacenar en búfer toda la salida</li>
                        <li>Mover toda la lógica de cookies y encabezados al principio del script</li>
                        <li>Separar la lógica en archivos diferentes (uno para encabezados, otro para salida)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabId) {
            // Ocultar todos los contenidos de pestañas
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Desactivar todas las pestañas
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activar la pestaña seleccionada
            document.getElementById(tabId).classList.add('active');
            
            // Activar el botón de la pestaña
            const activeTab = Array.from(tabs).find(tab => tab.textContent.toLowerCase().includes(tabId.replace('-', ' ')));
            if (activeTab) {
                activeTab.classList.add('active');
            }
        }
    </script>
</body>
</html>
