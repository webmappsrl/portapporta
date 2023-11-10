<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Address;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required'],
                'password' => ['required'],
                'app_company_id' => ['required', 'integer']
            ]);

            if (Auth::attempt($request->only('email', 'password'))) {
                $user = Auth::user();
                if ($user->app_company_id != $request->app_company_id) {
                    $message = 'Non puoi accedere a questa app.';
                    $company = Company::find($user->app_company_id);
                    if ($company) {
                        $message = $message . ' Sei registrato all\'app: ' . $company->name . '.';
                    }
                    throw ValidationException::withMessages([
                        'email' => [$message]
                    ]);
                }
                $success['token'] =  $user->createToken('access_token')->plainTextToken;
                $success['name'] =  $user->name;
                $success['email_verified_at'] =  $user->email_verified_at;

                $query = Address::where('user_id', $user->id)->get();
                $addresses = collect($query)->map(function ($address, $key) {
                    $address->location = $this->getLocation($address->location);
                    return $address;
                });

                if (!$addresses->isEmpty()) {
                    $user->addresses = json_decode($addresses);
                }

                $success['user'] = $user;

                return $this->sendResponse($success, 'User login successfully.');
            }

            throw ValidationException::withMessages([
                'email' => ['Le credenziali inserite non sono corrette.']
            ]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function logout()
    {
        Auth::logout();
    }

    public function getLocation($location)
    {
        $g = json_decode(DB::select("SELECT st_asgeojson('$location') as g")[0]->g);

        return [$g->coordinates[0], $g->coordinates[1]];
    }
}
