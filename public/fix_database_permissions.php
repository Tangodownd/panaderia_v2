<?php
// Este script verifica y corrige los permisos de la base de datos
// Colócalo en la carpeta public y ejecútalo desde el navegador

// Cargar el framework de Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

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
    <title>Corrección de Permisos de Base de Datos</title>
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
        <h1>Corrección de Permisos de Base de Datos</h1>";

// Verificar si se ha enviado el formulario para corregir los permisos
if (isset($_POST['fix_permissions'])) {
    try {
        // Obtener información de la conexión a la base de datos
        $connection = DB::connection();
        $database = $connection->getDatabaseName();
        $username = env('DB_USERNAME');
        
        // Mostrar información de la conexión
        echo "<div class='card mb-4'>
            <div class='card-header bg-primary text-white'>
                <h5 class='mb-0'>Información de la Conexión</h5>
            </div>
            <div class='card-body'>";
        
        echo "<p><strong>Base de datos:</strong> $database</p>";
        echo "<p><strong>Usuario:</strong> $username</p>";
        
        // Verificar permisos actuales
        $permissions = DB::select("SHOW GRANTS FOR CURRENT_USER()");
        
        echo "<h5>Permisos actuales:</h5>";
        echo "<pre>" . print_r($permissions, true) . "</pre>";
        
        // Intentar otorgar todos los permisos al usuario actual
        DB::statement("GRANT ALL PRIVILEGES ON `$database`.* TO '$username'@'%'");
        DB::statement("FLUSH PRIVILEGES");
        
        showMessage("Permisos corregidos correctamente", 'success');
        
        // Verificar permisos después de la corrección
        $newPermissions = DB::select("SHOW GRANTS FOR CURRENT_USER()");
        
        echo "<h5>Permisos después de la corrección:</h5>";
        echo "<pre>" . print_r($newPermissions, true) . "</pre>";
        
        echo "</div></div>";
    } catch (\Exception $e) {
        showMessage("Error al corregir permisos: " . $e->getMessage(), 'error');
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Verificar si se ha enviado el formulario para ejecutar migraciones
if (isset($_POST['run_migrations'])) {
    try {
        // Ejecutar migraciones
        Artisan::call('migrate');
        
        showMessage("Migraciones ejecutadas correctamente", 'success');
        
        // Mostrar salida de las migraciones
        $output = Artisan::output();
        
        echo "<pre>$output</pre>";
    } catch (\Exception $e) {
        showMessage("Error al ejecutar migraciones: " . $e->getMessage(), 'error');
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Verificar si se ha enviado el formulario para verificar la estructura de la base de datos
if (isset($_POST['check_structure'])) {
    try {
        // Verificar estructura de las tablas
        $tables = ['carts', 'cart_items', 'orders', 'order_items'];
        
        echo "<div class='card mb-4'>
            <div class='card-header bg-primary text-white'>
                <h5 class='mb-0'>Estructura de las Tablas</h5>
            </div>
            <div class='card-body'>";
        
        foreach ($tables as $table) {
            echo "<h5>Tabla: $table</h5>";
            
            if (Schema::hasTable($table)) {
                $columns = Schema::getColumnListing($table);
                
                echo "<ul>";
                foreach ($columns as $column) {
                    $type = DB::getSchemaBuilder()->getColumnType($table, $column);
                    echo "<li><strong>$column:</strong> $type</li>";
                }
                echo "</ul>";
            } else {
                showMessage("La tabla $table no existe", 'error');
            }
        }
        
        echo "</div></div>";
    } catch (\Exception $e) {
        showMessage("Error al verificar estructura: " . $e->getMessage(), 'error');
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Verificar si se ha enviado el formulario para reparar las tablas
if (isset($_POST['repair_tables'])) {
    try {
        // Reparar tablas
        $tables = ['carts', 'cart_items', 'orders', 'order_items'];
        
        echo "<div class='card mb-4'>
            <div class='card-header bg-primary text-white'>
                <h5 class='mb-0'>Reparación de Tablas</h5>
            </div>
            <div class='card-body'>";
        
        foreach ($tables as $table) {
            echo "<h5>Tabla: $table</h5>";
            
            if (Schema::hasTable($table)) {
                $result = DB::select("REPAIR TABLE $table");
                echo "<pre>" . print_r($result, true) . "</pre>";
            } else {
                showMessage("La tabla $table no existe", 'error');
            }
        }
        
        echo "</div></div>";
    } catch (\Exception $e) {
        showMessage("Error al reparar tablas: " . $e->getMessage(), 'error');
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Verificar si se ha enviado el formulario para optimizar las tablas
if (isset($_POST['optimize_tables'])) {
    try {
        // Optimizar tablas
        $tables = ['carts', 'cart_items', 'orders', 'order_items'];
        
        echo "<div class='card mb-4'>
            <div class='card-header bg-primary text-white'>
                <h5 class='mb-0'>Optimización de Tablas</h5>
            </div>
            <div class='card-body'>";
        
        foreach ($tables as $table) {
            echo "<h5>Tabla: $table</h5>";
            
            if (Schema::hasTable($table)) {
                $result = DB::select("OPTIMIZE TABLE $table");
                echo "<pre>" . print_r($result, true) . "</pre>";
            } else {
                showMessage("La tabla $table no existe", 'error');
            }
        }
        
        echo "</div></div>";
    } catch (\Exception $e) {
        showMessage("Error al optimizar tablas: " . $e->getMessage(), 'error');
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// Mostrar formulario para corregir los permisos
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Corregir Permisos</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <p>Esta acción intentará otorgar todos los permisos necesarios al usuario de la base de datos.</p>
            <input type='hidden' name='fix_permissions' value='1'>
            <button type='submit' class='btn btn-success'>Corregir Permisos</button>
        </form>
    </div>
</div>";

// Mostrar formulario para ejecutar migraciones
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Ejecutar Migraciones</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <p>Esta acción ejecutará las migraciones pendientes.</p>
            <input type='hidden' name='run_migrations' value='1'>
            <button type='submit' class='btn btn-success'>Ejecutar Migraciones</button>
        </form>
    </div>
</div>";

// Mostrar formulario para verificar la estructura de la base de datos
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Verificar Estructura</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <p>Esta acción verificará la estructura de las tablas.</p>
            <input type='hidden' name='check_structure' value='1'>
            <button type='submit' class='btn btn-success'>Verificar Estructura</button>
        </form>
    </div>
</div>";

// Mostrar formulario para reparar las tablas
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Reparar Tablas</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <p>Esta acción intentará reparar las tablas.</p>
            <input type='hidden' name='repair_tables' value='1'>
            <button type='submit' class='btn btn-success'>Reparar Tablas</button>
        </form>
    </div>
</div>";

// Mostrar formulario para optimizar las tablas
echo "<div class='card mb-4'>
    <div class='card-header bg-primary text-white'>
        <h5 class='mb-0'>Optimizar Tablas</h5>
    </div>
    <div class='card-body'>
        <form method='post'>
            <p>Esta acción intentará optimizar las tablas.</p>
            <input type='hidden' name='optimize_tables' value='1'>
            <button type='submit' class='btn btn-success'>Optimizar Tablas</button>
        </form>
    </div>
</div>";

// Finalizar la salida HTML
echo "</div>
</body>
</html>";
?>

