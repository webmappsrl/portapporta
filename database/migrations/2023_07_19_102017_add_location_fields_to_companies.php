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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('default_zoom')->default(5);
            $table->string('min_zoom')->default(5);
            $table->string('max_zoom')->default(17);
            $table->point('location')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('min_zoom');
            $table->dropColumn('start_zoom');
            $table->dropColumn('max_zoom');
            $table->dropColumn('location');
        });
    }
};
