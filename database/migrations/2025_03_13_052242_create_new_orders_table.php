<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateNewOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Crear la nueva tabla con todos los campos necesarios
        Schema::create('orders_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->string('status');
            $table->text('shipping_address');
            $table->string('payment_method');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Copiar datos de la tabla antigua a la nueva (si hay datos)
        if (Schema::hasTable('orders')) {
            $orders = DB::table('orders')->get();
            foreach ($orders as $order) {
                DB::table('orders_new')->insert([
                    'id' => $order->id,
                    'user_id' => $order->user_id,
                    'total' => $order->total,
                    'status' => $order->status,
                    'shipping_address' => $order->shipping_address,
                    'payment_method' => $order->payment_method,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at
                ]);
            }
        }

        // Eliminar la tabla antigua
        Schema::dropIfExists('orders');

        // Renombrar la nueva tabla
        Schema::rename('orders_new', 'orders');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Crear la tabla original
        Schema::create('orders_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->decimal('total', 10, 2)->default(0);
            $table->string('status');
            $table->string('shipping_address');
            $table->string('payment_method');
            $table->timestamps();
        });

        // Copiar datos de la tabla nueva a la antigua
        $orders = DB::table('orders')->get();
        foreach ($orders as $order) {
            DB::table('orders_old')->insert([
                'id' => $order->id,
                'user_id' => $order->user_id ?? 1, // Valor por defecto si es null
                'total' => $order->total,
                'status' => $order->status,
                'shipping_address' => $order->shipping_address,
                'payment_method' => $order->payment_method,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at
            ]);
        }

        // Eliminar la tabla nueva
        Schema::dropIfExists('orders');

        // Renombrar la tabla antigua
        Schema::rename('orders_old', 'orders');
    }
}

