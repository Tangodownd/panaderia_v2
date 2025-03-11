<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalFieldsToBlogsTable extends Migration
{
    public function up()
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('sku')->nullable();
            $table->float('weight')->nullable();
            $table->json('dimensions')->nullable();
            $table->string('warrantyInformation')->nullable();
            $table->string('shippingInformation')->nullable();
            $table->string('availabilityStatus')->nullable();
            $table->json('reviews')->nullable();
            $table->string('returnPolicy')->nullable();
            $table->integer('minimumOrderQuantity')->nullable();

        });
    }

    public function down()
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn([
                'sku', 'weight', 'dimensions', 'warrantyInformation', 
                'shippingInformation', 'availabilityStatus', 'reviews', 
                'returnPolicy', 'minimumOrderQuantity'
            ]);
        });
    }
}