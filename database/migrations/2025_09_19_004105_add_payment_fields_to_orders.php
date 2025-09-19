<?php

// database/migrations/2025_09_18_120000_add_payment_fields_to_orders.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('orders', function (Blueprint $t) {
      if (!Schema::hasColumn('orders','status')) {
        $t->string('status')->default('awaiting_payment')->index();
      }
      if (!Schema::hasColumn('orders','payment_method')) {
        $t->string('payment_method')->nullable()->index(); // cash|transfer|zelle|mobile|card...
      }
      if (!Schema::hasColumn('orders','payment_proof_path')) {
        $t->string('payment_proof_path')->nullable();
      }
      if (!Schema::hasColumn('orders','payment_verified_at')) {
        $t->timestamp('payment_verified_at')->nullable();
      }
      if (!Schema::hasColumn('orders','notes')) {
        $t->text('notes')->nullable();
      }
    });
  }
  public function down(): void {
    Schema::table('orders', function (Blueprint $t) {
      $t->dropColumn(['payment_method','payment_proof_path','payment_verified_at']); 
      // status/notes las puedes dejar si ya existían
    });
  }
};
