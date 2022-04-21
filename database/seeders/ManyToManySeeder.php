<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\UserType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ManyToManySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach(Company::all() as $company) {

            $usertypes = $company->userTypes->pluck('id')->toArray();
            $trashtypes = $company->trashTypes->pluck('id')->toArray();

            foreach ($company->zones as $zone) {
                $zone->userTypes()->attach(array_slice(Arr::shuffle($usertypes),rand(0,count($usertypes) - 1)));
            }

            foreach ($company->wasteCollectionCenters as $center) {
                $center->userTypes()->attach(array_slice(Arr::shuffle($usertypes),rand(0,count($usertypes) - 1)));
                $center->trashTypes()->attach(array_slice(Arr::shuffle($trashtypes),rand(0,count($trashtypes) - 1)));
            }
            
        }

    }
}
