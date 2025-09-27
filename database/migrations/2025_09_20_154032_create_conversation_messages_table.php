<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('conversation_messages', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('conversation_id');
      $table->enum('role', ['user','assistant','system']);
      $table->text('text');
      $table->json('metadata')->nullable(); // intent, entities, etc.
      $table->timestamps();

      $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
    });
  }
  public function down(): void { Schema::dropIfExists('conversation_messages'); }
};
