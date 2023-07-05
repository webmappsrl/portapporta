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
            $table->text('header')->nullable();
            $table->text('footer')->nullable();
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->text('css_variables')->nullable();
            $table->string('font')->nullable();
            $table->string('sku')->unique()->nullable();
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
            $table->dropIfExists('header');
            $table->dropIfExists('footer');
            $table->dropIfExists('primary_color');
            $table->dropIfExists('secondary_color');
            $table->dropIfExists('css_variables');
            $table->dropIfExists('font');
            $table->dropIfExists('sku');
        });
    }
};
