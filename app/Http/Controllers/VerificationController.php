<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class VerificationController extends Controller
{
    public function verify($user_id, Request $request)
    {
        if (!$request->hasValidSignature()) {
            return $this->sendError("Invalid/Expired url provided.");
        }

        try {
            $user = User::findOrFail($user_id);
        } catch (\Exception $e) {
            return $this->sendError("User not found.");
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            return view('auth.verifyemail', [
                'user' => $user,
                'already_validated' => false
            ]);
        }

        return view('auth.verifyemail', [
            'user' => $user,
            'already_validated' => true
        ]);
    }

    public function resend()
    {
        $userFromAuth =  auth('sanctum')->user();
        if ($userFromAuth === null) {
            return $this->sendError([], "you are not registered");
        }
        $userID = $userFromAuth->id;
        try {
            $user = User::findOrFail($userID);
        } catch (\Exception $e) {
            return $this->sendError([], "User not found.");
        }

        if ($user->hasVerifiedEmail()) {
            return $this->sendResponse([], "Email already verified.");
        }

        $user->sendEmailVerificationNotification();
        return $this->sendResponse([], "Email verification link sent on your email id");
    }
}
