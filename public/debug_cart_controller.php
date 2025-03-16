<?php
// public/debug_cart_controller.php

// Incluir el autoloader de Composer
require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Conectar a la base de datos
$db = new PDO(
    "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_DATABASE'],
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD']
);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Verificar tablas
echo "<h1>Depuración de Carrito</h1>";

echo "<h2>Estructura de tablas</h2>";
$tables = ['carts', 'cart_items', 'products'];
foreach ($tables as $table) {
    echo "<h3>Tabla: $table</h3>";
    try {
        $stmt = $db->query("DESCRIBE $table");
        echo "<pre>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            print_r($row);
        }
        echo "</pre>";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Verificar datos
echo "<h2>Datos en las tablas</h2>";

echo "<h3>Carritos</h3>";
try {
    $stmt = $db->query("SELECT * FROM carts LIMIT 10");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

echo "<h3>Items del carrito</h3>";
try {
    $stmt = $db->query("SELECT * FROM cart_items LIMIT 10");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Verificar sesión actual
echo "<h2>Sesión actual</h2>";
echo "Session ID: " . session_id();