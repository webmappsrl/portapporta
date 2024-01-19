<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Models\Address;
use App\Models\UserType;
use App\Models\Zone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $user_id = $user->id;
            $addresses = Address::where('user_id', $user_id)->with('zone')->get();
            $addressesByZone = $addresses->groupBy('zone_id');
            $result = [];
            foreach ($addressesByZone as $zone_id => $zoneAddresses) {
                $zone = Zone::find($zone_id);
                $zoneData = $zone->toArray();
                if ($zoneData) {
                    unset($zoneData['geometry']);
                    unset($zoneData['company_id']);
                    unset($zoneData['created_at']);
                    unset($zoneData['updated_at']);
                    unset($zoneData['url']);
                }
                $avalaibleUserTypes = $zone->userTypes->map(function ($userType) {
                    unset($userType['created_at']);
                    unset($userType['updated_at']);
                    unset($userType['company_id']);
                    unset($userType['slug']);
                    unset($userType['pivot']);
                    return $userType;
                });
                $zoneData['avalaible_user_types'] = $avalaibleUserTypes;
                $zoneData['addresses'] = $zoneAddresses->map(function ($address) {
                    $addressData = $address->toArray();
                    $addressData['location'] = $this->getLocationFromGeometry($addressData['location']);
                    unset($addressData['zone']);
                    unset($addressData['user_id']);
                    unset($addressData['created_at']);
                    unset($addressData['updated_at']);

                    return $addressData;
                })->toArray();
                $result[] = $zoneData;
            }
            $success['zones'] =  $result;
            return $this->sendResponse($success, 'calendar types');
            throw ValidationException::withMessages([
                'wrong' => ['Something get wrong']
            ]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(UpdateAddressRequest $request)
    {
        try {
            $authUser = Auth::user();
            if (isset($request->city) && isset($request->address) && isset($request->location)) {
                $address = Address::create([
                    'user_id' => $authUser->id,
                    'address' => $request->address,
                    'city' => $request->city,
                    'house_number' => $request->house_number,
                    'zone_id' => $request->zone_id,
                    'user_type_id' => $request->user_type_id,
                    'location' => $this->getGeometryFromLocation($request->location)
                ]);
                $address->location = $request->location;
                $success['address'] = $address;
                return $this->sendResponse($success, 'address correctly created');
            } else {
                throw ValidationException::withMessages([
                    'wrong' => ['missed required fields']
                ]);
            }

            throw ValidationException::withMessages([
                'wrong' => ['Something get wrong']
            ]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreAddressRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAddressRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function show(Address $address)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function edit(Address $address)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAddressRequest  $request
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAddressRequest $request)
    {
        try {
            $authUser = Auth::user();
            $address = Address::find($request->id);
            if (is_null($address)) {
                throw ValidationException::withMessages([
                    'wrong' => ['address no avalaiable on db']
                ]);
            }
            if ($authUser->id === $address->user_id) {
                if (isset($request->address)) {
                    $address->address = $request->address;
                }
                if (isset($request->location)) {
                    $address->location = $this->getGeometryFromLocation($request->location);
                }
                if (isset($request->user_type_id)) {
                    $address->user_type_id = $request->user_type_id;
                }
                if (isset($request->house_number)) {
                    $address->house_number = $request->house_number;
                }
                if (isset($request->city)) {
                    $address->city = $request->city;
                }
                if (isset($request->zone_id)) {
                    $address->zone_id = $request->zone_id;
                }
                $address->save();
                $success['address'] =  $address;
                return $this->sendResponse($success, 'address correctly updated');
            } else {
                throw ValidationException::withMessages([
                    'wrong' => ['address is not propery of this user']
                ]);
            }
            throw ValidationException::withMessages([
                'wrong' => ['Something get wrong']
            ]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        try {
            $authUser = Auth::user();
            $address = Address::find($request->id);
            if (isset($address) === false) {
                throw ValidationException::withMessages([
                    'wrong' => ['address no avalaiable on db']
                ]);
            }
            if ($authUser->id === $address->user_id) {
                Address::destroy($request->id);
                $success['address'] =  $address;
                return $this->sendResponse($success, 'address correctly deleted');
            } else {
                throw ValidationException::withMessages([
                    'wrong' => ['address is not propery of this user']
                ]);
            }
            throw ValidationException::withMessages([
                'wrong' => ['Something get wrong']
            ]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }


    private function getGeometryFromLocation($location)
    {
        return DB::select("SELECT ST_GeomFromText('POINT(" . $location[0] . " " . $location[1] . " )') as g")[0]->g;
    }

    private  function getLocationFromGeometry($location)
    {
        $g = json_decode(DB::select("SELECT st_asgeojson('$location') as g")[0]->g);

        return [$g->coordinates[0], $g->coordinates[1]];
    }
}
