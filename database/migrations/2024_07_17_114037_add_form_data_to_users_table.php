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
        Schema::table('users', function (Blueprint $table) {
            $table->json('form_data')->nullable();
        });

        DB::table('users')->get()->each(function ($user) {
            $formData = [];

            if (!is_null($user->phone_number)) {
                $formData['phone_number'] = $user->phone_number;
            }
            if (!is_null($user->fiscal_code)) {
                $formData['fiscal_code'] = $user->fiscal_code;
            }
            if (!is_null($user->user_code)) {
                $formData['user_code'] = $user->user_code;
            }

            if(count($formData) > 0){
                DB::table('users')->where('id', $user->id)->update([
                    'form_data' => json_encode($formData)
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('form_data');
        });
    }
};
