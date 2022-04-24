<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_type_waste_collection_center', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('user_type_id');
            $table->foreignId('waste_collection_center_id');

            $table->foreign('user_type_id')->references('id')->on('user_types')->onDelete('cascade');
            $table->foreign('waste_collection_center_id')->references('id')->on('waste_collection_centers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_type_waste_collection_center');
    }
};
