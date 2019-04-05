<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\User;

class AuthController extends Controller
{
    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function signup(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|unique:users',
        ]);

        $user = new User([
            'phone_number' => $request->phone_number,
        ]);

        $user->save();
        $created_user = [
            'phone_number' => $user->phone_number,
//            'remember_me' => '1'
        ];

        $new_request = new \Illuminate\Http\Request();
        $new_request->replace($created_user);
        $login_response = app('App\Http\Controllers\AuthController')->login($new_request);

        return $login_response;
    }

    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
//            'remember_me' => 'boolean'
        ]);


        if(!Auth::attempt(['phone_number' => $request->phone_number]))
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);

        $user = User::where('phone_number', $request->phone_number)->first();

        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(300);

        $token->save();

        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ]);

    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}