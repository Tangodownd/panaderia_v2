<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerifyDatabaseStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica la estructura de la base de datos para el sistema de panadería';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Verificando estructura de la base de datos...');
        
        // Verificar conexión a la base de datos
        try {
            DB::connection()->getPdo();
            $this->info('✓ Conexión a la base de datos establecida correctamente');
            Log::info('Conexión a la base de datos establecida correctamente');
        } catch (\Exception $e) {
            $this->error('✗ Error de conexión a la base de datos: ' . $e->getMessage());
            Log::error('Error de conexión a la base de datos: ' . $e->getMessage());
            return 1;
        }
        
        // Tablas que deberían existir
        $tables = ['carts', 'cart_items', 'orders', 'order_items', 'products', 'categories'];
        
        $this->info('Verificando tablas...');
        
        $allTablesExist = true;
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $this->info("✓ Tabla '{$table}' existe");
                
                // Contar registros
                $count = DB::table($table)->count();
                $this->info("  - Registros: {$count}");
                
                // Mostrar columnas
                $columns = Schema::getColumnListing($table);
                $this->info("  - Columnas: " . implode(', ', $columns));
            } else {
                $this->error("✗ Tabla '{$table}' NO existe");
                $allTablesExist = false;
            }
        }
        
        if (!$allTablesExist) {
            $this->warn('Algunas tablas necesarias no existen. Ejecuta las migraciones con: php artisan migrate');
        } else {
            $this->info('Todas las tablas necesarias existen.');
        }
        
        // Verificar estructura específica de tablas críticas
        $this->info('Verificando estructura de tablas críticas...');
        
        // Verificar orders
        if (Schema::hasTable('orders')) {
            $orderColumns = Schema::getColumnListing('orders');
            $requiredOrderColumns = ['id', 'user_id', 'session_id', 'order_number', 'total', 'status', 'shipping_address', 'payment_method', 'name', 'email', 'phone', 'notes', 'created_at', 'updated_at'];
            
            $missingOrderColumns = array_diff($requiredOrderColumns, $orderColumns);
            
            if (empty($missingOrderColumns)) {
                $this->info('✓ Tabla orders tiene todas las columnas requeridas');
            } else {
                $this->error('✗ Tabla orders le faltan columnas: ' . implode(', ', $missingOrderColumns));
            }
        }
        
        // Verificar order_items
        if (Schema::hasTable('order_items')) {
            $orderItemColumns = Schema::getColumnListing('order_items');
            $requiredOrderItemColumns = ['id', 'order_id', 'product_id', 'quantity', 'price', 'created_at', 'updated_at'];
            
            $missingOrderItemColumns = array_diff($requiredOrderItemColumns, $orderItemColumns);
            
            if (empty($missingOrderItemColumns)) {
                $this->info('✓ Tabla order_items tiene todas las columnas requeridas');
            } else {
                $this->error('✗ Tabla order_items le faltan columnas: ' . implode(', ', $missingOrderItemColumns));
            }
        }
        
        $this->info('Verificación completada.');
        return 0;
    }
}

