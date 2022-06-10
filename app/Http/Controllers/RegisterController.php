<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Exception;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required'],
                'company_id' => ['required'],
                'email' => ['required', 'email', 'unique:users'],
                'password' => ['required', 'min:8', 'confirmed'],
                'password_confirmation' => ['required'],
                'zone_id' => ['required'],
                'user_type_id' => ['required'],
                'location' => ['required']
            ]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        // TODO: add company_id association with companies table
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'zone_id' => $request->zone_id,
            'user_type_id' => $request->user_type_id,
            'location' => DB::select("SELECT ST_GeomFromText('POINT(" . $request->location[0] . " " . $request->location[1] . " )') as g")[0]->g,
        ]);

        $success['token'] =  $user->createToken('access_token')->plainTextToken;
        $success['name'] =  $user->name;
        event(new Registered($user));
        return $this->sendResponse($success, 'User register successfully.');
    }
}
