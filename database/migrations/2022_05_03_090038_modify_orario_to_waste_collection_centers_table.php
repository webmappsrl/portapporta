<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            DB::statement('ALTER TABLE waste_collection_centers ALTER COLUMN orario TYPE text;');
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
            DB::statement('ALTER TABLE waste_collection_centers ALTER COLUMN orario TYPE varchar(255);');
        });
    }
};
