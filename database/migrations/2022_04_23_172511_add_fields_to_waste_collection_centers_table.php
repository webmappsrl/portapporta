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
        Schema::table('waste_collection_centers', function (Blueprint $table) {
            // Translatable
            $table->string('name');
            $table->string('orario')->nullable();
            $table->text('description')->nullable();

            // Not translatable
            $table->string('marker_color')->nullable();
            $table->string('marker_size')->nullable();
            $table->string('website')->nullable();
            $table->string('picture_url')->nullable();

            // Geometry
            $table->point('geometry')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('waste_collection_centers', function (Blueprint $table) {
            $table->dropColumn('marker_color');
            $table->dropColumn('marker_size');
            $table->dropColumn('website');
            $table->dropColumn('picture_url');
            $table->dropColumn('name');
            $table->dropColumn('orario');
            $table->dropColumn('description');
            $table->dropColumn('geometry');
        });
    }
};
