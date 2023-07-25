<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Exception;

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
                $success['user']['location'] = $this->getLocation($user);
                return $this->sendResponse($success, 'user name changed successfully.');
            }

            if ($request->has('password') && $request->has('password_confirmation') && $numberOfFields === 2) {
                $user->password = Hash::make($request->password);
                $user->save();
                $success['user'] =  $user;
                $success['user']['location'] = $this->getLocation($user);
                return $this->sendResponse($success, 'password changed successfully.');
            }

            if ($request->has('zone_id') && $request->has('user_type_id') && $request->has('location') && $numberOfFields === 3) {
                $user->zone_id = $request->zone_id;
                $user->user_type_id = $request->user_type_id;
                $user->location = DB::select("SELECT ST_GeomFromText('POINT(" . $request->location[1] . " " . $request->location[0] . " )') as g")[0]->g;
                $user->save();
                $success['user'] =  $user;
                $success['user']['location'] = $this->getLocation($user);
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

    private  function getLocation($user)
    {
        $g = json_decode(DB::select("SELECT st_asgeojson('$user->location') as g")[0]->g);

        return [$g->coordinates[1], $g->coordinates[0]];
    }
}
