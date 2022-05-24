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
        Schema::table('trash_types', function (Blueprint $table) {
            $table->boolean('show_in_reservation')->default(true);
            $table->boolean('show_in_info')->default(true);
            $table->boolean('show_in_abandonment')->default(true);
            $table->boolean('show_in_report')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trash_types', function (Blueprint $table) {
            $table->dropColumn('show_in_reservation');
            $table->dropColumn('show_in_info');
            $table->dropColumn('show_in_abandonment');
            $table->dropColumn('show_in_report');
        });
    }
};
