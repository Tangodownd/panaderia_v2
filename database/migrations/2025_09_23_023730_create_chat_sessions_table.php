<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index()->unique();
            $table->string('step')->nullable();     // nombre del paso actual (ask_phone, ask_address, etc.)
            $table->json('data')->nullable();       // {name, phone, address, payment_method, payment_reference}
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('chat_sessions');
    }
};
