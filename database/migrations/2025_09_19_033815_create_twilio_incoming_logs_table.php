<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTwilioIncomingLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
Schema::create('twilio_incoming_logs', function (Blueprint $table) {
    $table->id();
    $table->string('from')->index();
    $table->string('to')->index();
    $table->text('body')->nullable();
    $table->json('payload')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('twilio_incoming_logs');
    }
}
