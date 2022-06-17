<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;

class VerificationController extends Controller
{
    public function verify($user_id, Request $request)
    {
        if (!$request->hasValidSignature()) {
            return $this->sendError("Invalid/Expired url provided.");
        }

        $user = User::findOrFail($user_id);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            return view('auth.verifyemail',[
                'user' => $user,
                'already_validated' => false
            ]);
        }

        return view('auth.verifyemail',[
            'user' => $user,
            'already_validated' => true
        ]);
    }

    public function resend()
    {
        $user = auth('sanctum')->user();
        if ($user === null) {
            return $this->sendError([], "you are not registered");
        }
        if ($user->hasVerifiedEmail()) {
            return $this->sendResponse([], "Email already verified.");
        }

        $user->sendEmailVerificationNotification();
        return $this->sendResponse([], "Email verification link sent on your email id");
    }
}
