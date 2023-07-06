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
            $table
                ->string('header_image')
                ->nullable()
                ->after('header');
            $table
                ->string('footer_image')
                ->nullable()
                ->after('footer');

            $table->string('app_icon')->nullable();
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
            $table->dropColumn([
                'header_image',
                'footer_image',
                'app_icon',
            ]);
        });
    }
};
