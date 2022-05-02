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
        Schema::table('wastes', function (Blueprint $table) {
            // Translatable
            $table->string('name');
            $table->text('where')->nullable();
            $table->text('notes')->nullable();

            // Not Translatable
            $table->boolean('pap')->default(false);
            $table->boolean('delivery')->default(false);
            $table->boolean('collection_center')->default(false);
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wastes', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('where');
            $table->dropColumn('notes');
            $table->dropColumn('pap');
            $table->dropColumn('delivery');
            $table->dropColumn('collection_center');
        });
    }
};
