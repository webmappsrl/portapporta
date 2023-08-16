<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAddressRequest;
use App\Http\Controllers\AddressController;
use App\Models\User;
use App\Models\Address;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class UpdateUserController extends Controller
{
    public function update(Request $request)
    {
        try {
            $authUser = Auth::user();
            $user = User::find($authUser->id);
            $numberOfFields = count($request->all());

            if ($request->has('name') && $numberOfFields === 1) {
                $user->name = $request->name;
                $user->save();
                $success['user'] =  $user;
                $success['user']['location'] = $this->getLocationByUser($user);
                return $this->sendResponse($success, 'user name changed successfully.');
            }

            if ($request->has('password') && $request->has('password_confirmation') && $numberOfFields === 2) {
                $user->password = Hash::make($request->password);
                $user->save();
                $success['user'] =  $user;
                $success['user']['location'] = $this->getLocationByUser($user);
                return $this->sendResponse($success, 'password changed successfully.');
            }

            if ($request->has('zone_id') && $request->has('user_type_id') && $request->has('location') && $numberOfFields === 3) {
                $user->zone_id = $request->zone_id;
                $user->user_type_id = $request->user_type_id;
                $user->location = DB::select("SELECT ST_GeomFromText('POINT(" . $request->location[1] . " " . $request->location[0] . " )') as g")[0]->g;
                $user->save();
                $success['user'] =  $user;
                $success['user']['location'] = $this->getLocationByUser($user);
                return $this->sendResponse($success, 'location and user type changed successfully.');
            }

            if ($request->has('fcm_token')) {
                $user->fcm_token = $request->fcm_token;
                $user->save();
                $success['user'] =  $user;
                return $this->sendResponse($success, 'fcm token changed successfully.');
            }

            throw ValidationException::withMessages([
                'wrong' => ['Something get wrong']
            ]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function v1Update(Request $request)
    {
        try {
            $authUser = Auth::user();
            $changes = [];
            $user = User::find($authUser->id);

            if ($request->has('name')) {
                $user->name = $request->name;
                array_push($changes, 'name');
            }
            if ($request->has('email')) {
                $user->email = $request->email;
                array_push($changes, 'email');
            }
            if ($request->has('user_code')) {
                $user->user_code = $request->user_code;
                array_push($changes, 'user_code');
            }
            if ($request->has('fiscal_code')) {
                $user->fiscal_code = $request->fiscal_code;
                array_push($changes, 'fiscal_code');
            }
            if ($request->has('phone_number')) {
                $user->phone_number = $request->phone_number;
                array_push($changes, 'phone_number');
            }
            if ($request->has('addresses')) {
                Log::info($request->addresses);
                foreach ($request->addresses as $address) {
                    if (isset($address['id'])) {
                        $updateAddressRequest = new UpdateAddressRequest();
                        $updateAddressRequest['id'] =  $address['id'];
                        $updateAddressRequest['address'] =  $address['address'];
                        $updateAddressRequest['location'] =  $address['location'];
                        (new AddressController)->update($updateAddressRequest);
                    } else {
                        $createAddressRequest = new UpdateAddressRequest();
                        $createAddressRequest['address'] =  $address['address'];
                        $createAddressRequest['location'] =  $address['location'];
                        (new AddressController)->create($createAddressRequest);
                    }
                    array_push($changes, 'addresses');
                }
                $user->addresses;
            }
            $user->save();
            $success['user'] =  $user;
            return $this->sendResponse($success, implode(",", $changes) . ': changed successfully.');

            throw ValidationException::withMessages([
                'wrong' => ['Something get wrong']
            ]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
    public function delete(Request $request)
    {
        try {
            $authUser = Auth::user();
            $user = User::find($authUser->id);
            $user->delete();
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        $success['user'] =  $user;
        return $this->sendResponse($success, 'user deleted successfully');
    }
    private  function getLocationByUser($user)
    {
        return $this->getLocation($user->location);
    }
    private  function getLocation($location)
    {
        $g = json_decode(DB::select("SELECT st_asgeojson('$location') as g")[0]->g);

        return [$g->coordinates[0], $g->coordinates[1]];
    }
    public function get(Request $request)
    {
        $user = $request->user();
        $query = Address::where('user_id', $user->id)->get();
        $addresses = collect($query)->map(function ($address, $key) {
            $address->location = $this->getLocation($address->location);
            return $address;
        });
        $user->addresses = json_decode($addresses);
        if ($user->location != null) {
            $geometry = $user->location;
            $g = json_decode(DB::select("SELECT st_asgeojson('$geometry') as g")[0]->g);
            $user->location = [$g->coordinates[1], $g->coordinates[0]];
        }

        return $user;
    }

    private function getGeometryFromLocation($location)
    {
        return DB::select("SELECT ST_GeomFromText('POINT(" . $location[1] . " " . $location[0] . " )') as g")[0]->g;
    }
}
