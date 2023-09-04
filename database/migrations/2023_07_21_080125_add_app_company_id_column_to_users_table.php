<?php

use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            $table->bigInteger('app_company_id')->nullable();
        });
        DB::transaction(function () {
            DB::table('users')->lazyById()->each(function ($user) {
                $zone_id = $user->zone_id;
                $zoneModel = Zone::find($zone_id);
                if (isset($zoneModel)) {
                    $company_id_from_zone = $zoneModel->company_id;
                    $userModel = User::find($user->id);
                    $userModel->app_company_id = $company_id_from_zone;
                    $userModel->save();
                }
            });
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
            $table->dropColumn('app_company_id');
        });
    }
};
