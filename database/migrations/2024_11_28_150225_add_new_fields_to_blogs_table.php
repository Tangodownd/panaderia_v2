<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldsToBlogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->decimal('precio', 8, 2)->default(0);
            $table->decimal('descuento', 5, 2)->default(0);
            $table->decimal('valoracion', 3, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->string('brand')->nullable();
            $table->string('thumbnail')->nullable();
            $table->json('etiquetas')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn(['precio', 'descuento', 'valoracion', 'stock', 'brand', 'thumbnail', 'etiquetas']);
        });
    }
}
