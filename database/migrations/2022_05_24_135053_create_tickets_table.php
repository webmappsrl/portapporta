<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * con il seguente datamodel
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {

            $table->id();
            $table->timestamps();
            $table->enum('ticket_type',['reservation', 'info','abandonment','report' ]);
            $table->point('geometry')->nullable();
            $table->text('note')->nullable();
            $table->text('phone')->nullable();

            $table->foreignId('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            $table->foreignId('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->foreignId('trash_type_id');
            $table->foreign('trash_type_id')->references('id')->on('trash_types')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tickets');
    }
};
