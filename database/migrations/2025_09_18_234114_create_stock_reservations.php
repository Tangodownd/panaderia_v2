<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('stock_reservations', function (Blueprint $t) {
      $t->id();
      $t->foreignId('product_id')->constrained()->cascadeOnDelete();
      $t->unsignedInteger('quantity');
      $t->foreignId('cart_id')->nullable()->constrained('carts')->nullOnDelete(); // opcional
      $t->string('session_id')->nullable(); // si el chatbot es anónimo
      $t->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
      $t->enum('status', ['reserved','consumed','released','expired'])->default('reserved');
      $t->timestamp('expires_at')->index();
      $t->timestamps();
      $t->index(['product_id','status','expires_at']);
    });
  }
  public function down(): void { Schema::dropIfExists('stock_reservations'); }
};
