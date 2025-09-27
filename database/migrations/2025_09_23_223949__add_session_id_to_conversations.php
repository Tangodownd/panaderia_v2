<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('session_id', 120)->nullable()->after('id')->index();
            // opcional: si quieres evitar duplicados por sesión
            // $table->unique('session_id');
        });
    }
    public function down(): void {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['session_id']);
            // $table->dropUnique(['session_id']);
            $table->dropColumn('session_id');
        });
    }
};
