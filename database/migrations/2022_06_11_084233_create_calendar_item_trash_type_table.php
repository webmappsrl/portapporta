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
        Schema::create('calendar_item_trash_type', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('calendar_item_id');
            $table->foreign('calendar_item_id')->references('id')->on('calendar_items')->onDelete('cascade');

            $table->foreignId('trash_type_id');
            $table->foreign('trash_type_id')->references('id')->on('trash_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('calendar_item_trash_type');
    }
};
