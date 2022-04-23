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
            $table->string('slug');
            $table->string('name');
            $table->string('where')->nullable();
            $table->text('howto')->nullable();
            $table->string('color')->nullable();
            $table->json('allowed')->nullable();
            $table->json('notallowed')->nullable();
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
            $table->dropColumn('slug');
            $table->dropColumn('name');
            $table->dropColumn('where');
            $table->dropColumn('howto');
            $table->dropColumn('color');
            $table->dropColumn('allowed');
            $table->dropColumn('notallowed');
        });
    }
};
