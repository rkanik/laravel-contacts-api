<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Passport;

class AuthController extends Controller
{
    public function register(Request $request)
    {

        if (empty($request->email) && empty($request->phone)) {
            return response()->json([
                'errors' => [
                    'email' => ['email is required.'],
                    'phone' => ['phone is required.'],
                ],
                'message' => 'User validation failed.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validation = Validator::make($request->all(), [
            'first_name' => 'required|max:16',
            'last_name' => 'required|max:16',
            'email' => 'required_if:phone,null|email|unique:users',
            'phone' => 'required_if:email,null|unique:users',
            'password' => 'required|confirmed',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors(),
                'message' => 'User validation failed.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Generating random username
        $name = $request->first_name . ' ' . $request->last_name;
        $username = randomUsername($name);
        $existed_user = User::where('username', 'rkani9088')->pluck('id')->first();
        while ($existed_user) {
            $username = randomUsername($name);
            $existed_user = User::where('username', $username)->first();
        }

        $new_user = [
            'role' => 'user',
            'username' => $username,

            'email' => $request->email,
            'phone' => $request->phone,

            'first_name' => $request->first_name,
            'last_name' => $request->last_name,

            'password' => Hash::make($request->password),
            'date_of_birth' => !empty($request->date_of_birth)
            ? Carbon::parse($request->date_of_birth)->format("Y-m-d")
            : null,
            'gender' => $request->gender,
        ];

        try {
            $user = User::create($new_user);
            return response()->json([
                'user' => $user,
                'message' => 'Registration successfull.',
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'message' => $ex->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

    }

    public function login(Request $request)
    {

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Incorrect username or passowrd',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // dd($request->remember_me);

        $user = Auth::user();

        $expires_at = null;
        if ($request->remember_me) {
            Passport::personalAccessTokensExpireIn(now()->addDays(30));
        }

        $token = $user->createToken('API ACCESS TOKEN');
        $access_token = $token->accessToken;

        if ($request->remember_me) {$expires_at = $token->token->expires_at;}

        return response()->json([
            "token_type" => "Bearer",
            "expires_at" => $expires_at,
            "access_token" => $access_token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'You have been successfully logged out!',
        ]);
    }
}
