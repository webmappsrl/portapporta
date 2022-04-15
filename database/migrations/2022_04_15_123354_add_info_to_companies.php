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
            $table->text('configTs')->nullable();
            $table->text('configJson')->nullable();
            $table->text('configXMLID')->nullable();
            $table->text('description')->nullable();
            $table->text('version')->nullable();
            $table->text('icon')->nullable();
            $table->text('splash')->nullable();
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
            $table->dropColumn('configTs');
            $table->dropColumn('configJson');
            $table->dropColumn('configXMLID');
            $table->dropColumn('description');
            $table->dropColumn('version');
            $table->dropColumn('icon');
            $table->dropColumn('splash');
        });
    }
};
