<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Exception;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required'],
                'password' => ['required']
            ]);

            if (Auth::attempt($request->only('email', 'password'))) {
                $user = Auth::user();
                $success['token'] =  $user->createToken('access_token')->plainTextToken;
                $success['name'] =  $user->name;
                $success['email_verified_at'] =  $user->email_verified_at;

                return $this->sendResponse($success, 'User login successfully.');
            }

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function logout()
    {
        Auth::logout();
    }
}
