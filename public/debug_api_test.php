<?php

// Incluir el autoloader de Composer
require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Función para realizar una solicitud a la API
function makeApiRequest($method, $endpoint, $data = null, $cookies = []) {
    $url = "http://" . $_SERVER['HTTP_HOST'] . "/api/" . $endpoint;
    
    $ch = curl_init($url);
    
    $headers = [
        'X-Requested-With: XMLHttpRequest',
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    // Configurar la solicitud cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Configurar el método HTTP
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } else if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    // Configurar cookies
    if (!empty($cookies)) {
        $cookieString = '';
        foreach ($cookies as $name => $value) {
            $cookieString .= $name . '=' . $value . '; ';
        }
        curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
    }
    
    // Capturar encabezados de respuesta
    $responseHeaders = [];
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$responseHeaders) {
        $len = strlen($header);
        $header = explode(':', $header, 2);
        if (count($header) < 2) // ignore invalid headers
            return $len;

        $name = trim($header[0]);
        $value = trim($header[1]);
        $responseHeaders[$name] = $value;
        
        return $len;
    });
    
    // Ejecutar la solicitud
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Decodificar la respuesta JSON
    $decodedResponse = json_decode($response, true);
    
    return [
        'status' => $httpCode,
        'headers' => $responseHeaders,
        'response' => $decodedResponse,
        'raw_response' => $response,
        'error' => $error
    ];
}

// Verificar si se está ejecutando una acción
$action = $_GET['action'] ?? 'view';

echo "<h1>Depuración de API</h1>";

// Ejecutar la acción seleccionada
switch ($action) {
    case 'view':
        // Mostrar formulario
        echo "<h2>Acciones disponibles</h2>";
        echo "<ul>";
        echo "<li><a href='?action=get_cart'>Obtener carrito</a></li>";
        echo "<li><a href='?action=add_to_cart'>Añadir producto al carrito</a></li>";
        echo "<li><a href='?action=update_cart_item'>Actualizar item del carrito</a></li>";
        echo "<li><a href='?action=remove_from_cart'>Eliminar producto del carrito</a></li>";
        echo "<li><a href='?action=clear_cart'>Vaciar carrito</a></li>";
        echo "</ul>";
        break;
        
    case 'get_cart':
        // Obtener el carrito actual
        echo "<h2>Obtener carrito</h2>";
        
        // Obtener la cookie de carrito
        $cartSessionId = $_COOKIE['cart_session_id'] ?? null;
        
        echo "<p>Cookie de carrito: " . ($cartSessionId ?: "No establecida") . "</p>";
        
        // Realizar la solicitud a la API
        $result = makeApiRequest('GET', 'cart', null, [
            'cart_session_id' => $cartSessionId
        ]);
        
        // Mostrar resultado
        echo "<h3>Resultado</h3>";
        echo "<p>Código de estado: " . $result['status'] . "</p>";
        
        echo "<h3>Encabezados de respuesta</h3>";
        echo "<pre>";
        print_r($result['headers']);
        echo "</pre>";
        
        echo "<h3>Respuesta</h3>";
        echo "<pre>";
        print_r($result['response']);
        echo "</pre>";
        
        // Verificar si hay una cookie de carrito en la respuesta
        if (isset($result['headers']['Set-Cookie']) && strpos($result['headers']['Set-Cookie'], 'cart_session_id') !== false) {
            echo "<p>La respuesta incluye una cookie de carrito.</p>";
        }
        
        echo "<p><a href='?action=view'>Volver</a></p>";
        break;
        
    case 'add_to_cart':
        // Mostrar formulario para añadir producto
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Obtener lista de productos
            try {
                $db = new PDO(
                    "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_DATABASE'],
                    $_ENV['DB_USERNAME'],
                    $_ENV['DB_PASSWORD']
                );
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $stmt = $db->query("SELECT id, name, price FROM products ORDER BY name");
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                echo "<p>Error al conectar con la base de datos: " . $e->getMessage() . "</p>";
                $products = [];
            }
            
            echo "<h2>Añadir producto al carrito</h2>";
            echo "<form method='post' action='?action=add_to_cart'>";
            echo "<div style='margin-bottom: 10px;'>";
            echo "<label for='product_id'>Producto:</label><br>";
            echo "<select name='product_id' id='product_id' required style='width: 300px;'>";
            
            foreach ($products as $product) {
                echo "<option value='{$product['id']}'>{$product['name']} - \${$product['price']}</option>";
            }
            
            echo "</select>";
            echo "</div>";
            
            echo "<div style='margin-bottom: 10px;'>";
            echo "<label for='quantity'>Cantidad:</label><br>";
            echo "<input type='number' name='quantity' id='quantity' value='1' min='1' style='width: 300px;'>";
            echo "</div>";
            
            echo "<div>";
            echo "<button type='submit'>Añadir al carrito</button>";
            echo "</div>";
            echo "</form>";
            
            echo "<p><a href='?action=view'>Volver</a></p>";
        } else {
            // Procesar formulario
            $productId = $_POST['product_id'] ?? null;
            $quantity = $_POST['quantity'] ?? 1;
            
            if (!$productId) {
                echo "<p>Error: No se ha seleccionado un producto</p>";
                echo "<p><a href='?action=add_to_cart'>Volver</a></p>";
                break;
            }
            
            // Obtener la cookie de carrito
            $cartSessionId = $_COOKIE['cart_session_id'] ?? null;
            
            echo "<h2>Añadir producto al carrito</h2>";
            echo "<p>Producto ID: $productId</p>";
            echo "<p>Cantidad: $quantity</p>";
            echo "<p>Cookie de carrito: " . ($cartSessionId ?: "No establecida") . "</p>";
            
            // Realizar la solicitud a la API
            $result = makeApiRequest('POST', 'cart/add', [
                'product_id' => $productId,
                'quantity' => $quantity
            ], [
                'cart_session_id' => $cartSessionId
            ]);
            
            // Mostrar resultado
            echo "<h3>Resultado</h3>";
            echo "<p>Código de estado: " . $result['status'] . "</p>";
            
            echo "<h3>Encabezados de respuesta</h3>";
            echo "<pre>";
            print_r($result['headers']);
            echo "</pre>";
            
            echo "<h3>Respuesta</h3>";
            echo "<pre>";
            print_r($result['response']);
            echo "</pre>";
            
            // Verificar si hay una cookie de carrito en la respuesta
            if (isset($result['headers']['Set-Cookie']) && strpos($result['headers']['Set-Cookie'], 'cart_session_id') !== false) {
                echo "<p>La respuesta incluye una cookie de carrito.</p>";
            }
            
            echo "<p><a href='?action=view'>Volver</a></p>";
        }
        break;
        
    case 'update_cart_item':
        // Mostrar formulario para actualizar item
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Obtener la cookie de carrito
            $cartSessionId = $_COOKIE['cart_session_id'] ?? null;
            
            if (!$cartSessionId) {
                echo "<p>Error: No hay cookie de carrito establecida</p>";
                echo "<p><a href='?action=view'>Volver</a></p>";
                break;
            }
            
            // Obtener el carrito actual
            $result = makeApiRequest('GET', 'cart', null, [
                'cart_session_id' => $cartSessionId
            ]);
            
            if ($result['status'] !== 200 || empty($result['response']['items'])) {
                echo "<p>Error: No se pudo obtener el carrito o está vacío</p>";
                echo "<p><a href='?action=view'>Volver</a></p>";
                break;
            }
            
            $items = $result['response']['items'];
            
            echo "<h2>Actualizar item del carrito</h2>";
            echo "<form method='post' action='?action=update_cart_item'>";
            echo "<div style='margin-bottom: 10px;'>";
            echo "<label for='product_id'>Producto:</label><br>";
            echo "<select name='product_id' id='product_id' required style='width: 300px;'>";
            
            foreach ($items as $item) {
                $productName = $item['product']['name'] ?? 'Producto ID: ' . $item['product_id'];
                echo "<option value='{$item['product_id']}'>{$productName} - Cantidad actual: {$item['quantity']}</option>";
            }
            
            echo "</select>";
            echo "</div>";
            
            echo "<div style='margin-bottom: 10px;'>";
            echo "<label for='quantity'>Nueva cantidad:</label><br>";
            echo "<input type='number' name='quantity' id='quantity' value='1' min='1' style='width: 300px;'>";
            echo "</div>";
            
            echo "<div>";
            echo "<button type='submit'>Actualizar cantidad</button>";
            echo "</div>";
            echo "</form>";
            
            echo "<p><a href='?action=view'>Volver</a></p>";
        } else {
            // Procesar formulario
            $productId = $_POST['product_id'] ?? null;
            $quantity = $_POST['quantity'] ?? 1;
            
            if (!$productId) {
                echo "<p>Error: No se ha seleccionado un producto</p>";
                echo "<p><a href='?action=update_cart_item'>Volver</a></p>";
                break;
            }
            
            // Obtener la cookie de carrito
            $cartSessionId = $_COOKIE['cart_session_id'] ?? null;
            
            echo "<h2>Actualizar item del carrito</h2>";
            echo "<p>Producto ID: $productId</p>";
            echo "<p>Nueva cantidad: $quantity</p>";
            echo "<p>Cookie de carrito: " . ($cartSessionId ?: "No establecida") . "</p>";
            
            // Realizar la solicitud a la API
            $result = makeApiRequest('POST', 'cart/update', [
                'product_id' => $productId,
                'quantity' => $quantity
            ], [
                'cart_session_id' => $cartSessionId
            ]);
            
            // Mostrar resultado
            echo "<h3>Resultado</h3>";
            echo "<p>Código de estado: " . $result['status'] . "</p>";
            
            echo "<h3>Encabezados de respuesta</h3>";
            echo "<pre>";
            print_r($result['headers']);
            echo "</pre>";
            
            echo "<h3>Respuesta</h3>";
            echo "<pre>";
            print_r($result['response']);
            echo "</pre>";
            
            echo "<p><a href='?action=view'>Volver</a></p>";
        }
        break;
        
    case 'remove_from_cart':
        // Mostrar formulario para eliminar producto
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Obtener la cookie de carrito
            $cartSessionId = $_COOKIE['cart_session_id'] ?? null;
            
            if (!$cartSessionId) {
                echo "<p>Error: No hay cookie de carrito establecida</p>";
                echo "<p><a href='?action=view'>Volver</a></p>";
                break;
            }
            
            // Obtener el carrito actual
            $result = makeApiRequest('GET', 'cart', null, [
                'cart_session_id' => $cartSessionId
            ]);
            
            if ($result['status'] !== 200 || empty($result['response']['items'])) {
                echo "<p>Error: No se pudo obtener el carrito o está vacío</p>";
                echo "<p><a href='?action=view'>Volver</a></p>";
                break;
            }
            
            $items = $result['response']['items'];
            
            echo "<h2>Eliminar producto del carrito</h2>";
            echo "<form method='post' action='?action=remove_from_cart'>";
            echo "<div style='margin-bottom: 10px;'>";
            echo "<label for='product_id'>Producto:</label><br>";
            echo "<select name='product_id' id='product_id' required style='width: 300px;'>";
            
            foreach ($items as $item) {
                $productName = $item['product']['name'] ?? 'Producto ID: ' . $item['product_id'];
                echo "<option value='{$item['product_id']}'>{$productName} - Cantidad: {$item['quantity']}</option>";
            }
            
            echo "</select>";
            echo "</div>";
            
            echo "<div>";
            echo "<button type='submit'>Eliminar del carrito</button>";
            echo "</div>";
            echo "</form>";
            
            echo "<p><a href='?action=view'>Volver</a></p>";
        } else {
            // Procesar formulario
            $productId = $_POST['product_id'] ?? null;
            
            if (!$productId) {
                echo "<p>Error: No se ha seleccionado un producto</p>";
                echo "<p><a href='?action=remove_from_cart'>Volver</a></p>";
                break;
            }
            
            // Obtener la cookie de carrito
            $cartSessionId = $_COOKIE['cart_session_id'] ?? null;
            
            echo "<h2>Eliminar producto del carrito</h2>";
            echo "<p>Producto ID: $productId</p>";
            echo "<p>Cookie de carrito: " . ($cartSessionId ?: "No establecida") . "</p>";
            
            // Realizar la solicitud a la API
            $result = makeApiRequest('POST', 'cart/remove', [
                'product_id' => $productId
            ], [
                'cart_session_id' => $cartSessionId
            ]);
            
            // Mostrar resultado
            echo "<h3>Resultado</h3>";
            echo "<p>Código de estado: " . $result['status'] . "</p>";
            
            echo "<h3>Encabezados de respuesta</h3>";
            echo "<pre>";
            print_r($result['headers']);
            echo "</pre>";
            
            echo "<h3>Respuesta</h3>";
            echo "<pre>";
            print_r($result['response']);
            echo "</pre>";
            
            echo "<p><a href='?action=view'>Volver</a></p>";
        }
        break;
        
    case 'clear_cart':
        // Mostrar confirmación para vaciar carrito
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo "<h2>Vaciar carrito</h2>";
            echo "<p>¿Estás seguro de que quieres vaciar el carrito?</p>";
            echo "<form method='post' action='?action=clear_cart'>";
            echo "<div>";
            echo "<button type='submit'>Sí, vaciar carrito</button>";
            echo "</div>";
            echo "</form>";
            
            echo "<p><a href='?action=view'>Volver</a></p>";
        } else {
            // Procesar formulario
            // Obtener la cookie de carrito
            $cartSessionId = $_COOKIE['cart_session_id'] ?? null;
            
            echo "<h2>Vaciar carrito</h2>";
            echo "<p>Cookie de carrito: " . ($cartSessionId ?: "No establecida") . "</p>";
            
            // Realizar la solicitud a la API
            $result = makeApiRequest('POST', 'cart/clear', null, [
                'cart_session_id' => $cartSessionId
            ]);
            
            // Mostrar resultado
            echo "<h3>Resultado</h3>";
            echo "<p>Código de estado: " . $result['status'] . "</p>";
            
            echo "<h3>Encabezados de respuesta</h3>";
            echo "<pre>";
            print_r($result['headers']);
            echo "</pre>";
            
            echo "<h3>Respuesta</h3>";
            echo "<pre>";
            print_r($result['response']);
            echo "</pre>";
            
            echo "<p><a href='?action=view'>Volver</a></p>";
        }
        break;
}

