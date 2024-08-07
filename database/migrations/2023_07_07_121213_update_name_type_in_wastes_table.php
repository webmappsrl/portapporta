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
        Schema::table('wastes', function (Blueprint $table) {
            DB::statement('ALTER TABLE wastes ALTER COLUMN name TYPE text;');
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
            DB::statement('ALTER TABLE wastes ALTER COLUMN name TYPE varchar(255);');
        });
    }
};
