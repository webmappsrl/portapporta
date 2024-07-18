<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Exception;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function register(Request $request){
        try {
            $request->validate([
                'name' => ['required'],
                'app_company_id' => ['required'],
                'email' => ['required', 'email', 'unique:users'],
                'password' => ['required', 'min:8', 'confirmed'],
                'password_confirmation' => ['required'],
            ]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'app_company_id' => intval($request->app_company_id),
            'phone_number' => $request->phone_number,
            'fiscal_code' => $request->fiscal_code,
            'user_code' => $request->user_code

        ]);
        $user->assignRole('contributor');
        try {
            Address::create([
                'user_id' => $user->id,
                'zone_id' => $request->zone_id,
                'user_type_id' => $request->user_type_id,
                'address' => $request->address,
                'city' => $request->city,
                'house_number' => $request->house_number,
                'location' => $this->getGeometryFromLocation($request->location),
            ]);
        } catch (\Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }

        $success['token'] =  $user->createToken('access_token')->plainTextToken;
        $success['name'] =  $user->name;
        event(new Registered($user));
        return $this->sendResponse($success, 'User register successfully.');
    }

    public function v1register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required'],
                'app_company_id' => ['required'],
                'email' => ['required', 'email', 'unique:users'],
                'password' => ['required', 'min:8', 'confirmed'],
                'password_confirmation' => ['required'],
            ]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        $formData = [];
        if (!is_null($request->phone_number)) {
            $formData['phone_number'] = $request->phone_number;
        }
        if (!is_null($request->fiscal_code)) {
            $formData['fiscal_code'] = $request->fiscal_code;
        }
        if (!is_null($request->user_code)) {
            $formData['user_code'] = $request->user_code;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'app_company_id' => intval($request->app_company_id),
            'form_data' => json_encode($formData)

        ]);
        $user->assignRole('contributor');
        try {
            Address::create([
                'user_id' => $user->id,
                'zone_id' => $request->zone_id,
                'user_type_id' => $request->user_type_id,
                'address' => $request->address,
                'city' => $request->city,
                'house_number' => $request->house_number,
                'location' => $this->getGeometryFromLocation($request->location),
            ]);
        } catch (\Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }

        $success['token'] =  $user->createToken('access_token')->plainTextToken;
        $success['name'] =  $user->name;
        event(new Registered($user));
        return $this->sendResponse($success, 'User register successfully.');
    }

    private function getGeometryFromLocation($location)
    {
        return DB::select("SELECT ST_GeomFromText('POINT(" . $location[0] . " " . $location[1] . " )') as g")[0]->g;
    }
}
