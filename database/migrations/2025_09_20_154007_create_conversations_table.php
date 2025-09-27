<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('conversations', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('customer_id')->nullable(); // enlaza si ya manejas customers
      $table->string('channel',50)->default('web');             // solo web
      $table->string('channel_user_id',100)->nullable();         // session_id del widget
      $table->unsignedBigInteger('cart_id')->nullable();     // carrito activo, si aplica
      $table->string('state',50)->default('idle');              // idle|collecting|confirming|closed
      $table->timestamps();

      $table->index(['channel', 'channel_user_id']);
    });
  }
  public function down(): void { Schema::dropIfExists('conversations'); }
};
