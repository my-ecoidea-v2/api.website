<?php

namespace App\Http\Controllers;

use App\User;
use App\Config;
use App\UserKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function authenticate(Request $request) 
    {
        $credentials = $request->only('email',  'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_email_or_password'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token, contact administrator'], 500);
        }
        
        if (Config::where('config', 'keysRequired')->get()->first()['value'] == 1)
        {
            $key = User::where('email', $request->get('email'))->get('key')->first()['key'];
            if (!UserKey::where('key', $key)->exists())
            {
                return response()->json([
                    'status' => 'error', 
                    'error' => 'invalid_key'
                ]);
            }
        }

        $user = User::where('email', $request->get('email'))->get()->first();
        return response()->json(compact('token', 'user'));
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string', 
        ]); if($validator->fails()){ return response()->json([
            'status' => 'error', 
            'error' => 'required_username', 
            'field' => 'name'
        ]); }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:75', 
        ]); if($validator->fails()){ return response()->json([
            'status' => 'error', 
            'error' => 'invalid_username', 
            'field' => 'name'
        ]); }

        $validator = Validator::make($request->all(), [
            'email' => 'required|string', 
        ]); if($validator->fails()){ return response()->json([
            'status' => 'error', 
            'error' => 'required_email', 
            'field' => 'email'
        ]); }

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:191', 
        ]); if($validator->fails()){ return response()->json([
            'status' => 'error', 
            'error' => 'invalid_email', 
            'field' => 'email'
        ]); }

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:191|unique:users', 
        ]); if($validator->fails()){ return response()->json([
            'status' => 'error', 
            'error' => 'used_email', 
            'field' => 'email'
        ]); }

        $validator = Validator::make($request->all(), [
            'password' => 'required|string', 
        ]); if($validator->fails()){ return response()->json([
            'status' => 'error', 
            'error' => 'required_password', 
            'field' => 'password'
        ]); }

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6', 
        ]); if($validator->fails()){ return response()->json([
            'status' => 'error', 
            'error' => 'invalid_password', 
            'field' => 'password'
        ]); }
        
        $validator = Validator::make($request->all(), [
            'password' => 'confirmed', 
        ]); if($validator->fails()){ return response()->json([
            'status' => 'error', 
            'error' => 'required_password_confirmation', 
            'field' => 'password_confirmation'
        ]); }

        if ($request->has('key')) 
        {
            $validator = Validator::make($request->all(), [
                'key' => 'string|unique:users', 
            ]); if($validator->fails()){ return response()->json([
                'status' => 'error', 
                'error' => 'used_key', 
                'field' => 'key'
            ]); }
            $key = $request->get('key');
            if (DB::table('keys')->where('key',  $key)->doesntExist()) {
                return response()->json(['error' => 'invalid_key']);
            }
        }

        // $confirmation_token = str_random(30);

        $user = User::create([
            'name' => $request->get('name'),
            'key' => $request->get('key'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password'))
            // 'confirmation_token' => $confirmation_token
        ]);

        // Mail::send('email.verify',  $confirmation_token, function($message) {
        //     $message
        //         ->to(Input::get('email'), Input::get('firstname'))
        //         ->subject('Verify your email address');
        // });

        $token = JWTAuth::fromUser($user);
        return response()->json(compact('user', 'token'),201);
    }

    public function confirm(Request $request)
    {
        if( ! $request)
        {
            throw new InvalidConfirmationCodeException;
        }

        $user = User::whereConfirmationCode($request)->first();

        if ( ! $user)
        {
            throw new InvalidConfirmationCodeException;
        }

        $user->confirmed = 1;
        $user->request = null;
        $user->save();

        Flash::message('You have successfully verified your account.');

        return Redirect::route('login_path');
    }

    public function update(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $id = $user['id'];
        $user = User::where('id', $id)->get()->first();


        $validator = Validator::make($request->all(), [
            'password' => 'required|string', 
        ]); if($validator->fails()){ return response()->json([
            'status' => 'error', 
            'error' => 'required_password', 
            'field' => 'password'
        ]); }
        $password = $request->get('password');
        if (password_verify($password,  User::where('id', $id)->value('password')) == false)
        {
            return response()->json([
                'status' => 'error', 
                'error' => 'bad_password'
            ]);
        }

        $validator = Validator::make($request->all(), ['new_name' => 'required|string']); 
        if($validator->fails()) { $user->name = $user['new_name']; }
        else { $user->name = $request->get('new_name'); }

        $validator = Validator::make($request->all(), ['new_email' => 'required|string']); 
        if($validator->fails()) { $user->email = $user['email']; }
        else { $user->email = $request->get('new_email'); }

        $validator = Validator::make($request->all(), ['new_password' => 'required|string']); 
        if($validator->fails()) { $user->password = $user['password']; }
        else { $user->password = Hash::make($request->get('new_password')); }
        
        $user->save();

        return response()->json(compact('user'));
    }

    public function delete(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $id = $user['id'];
        $user = User::where('id', $id)->get()->first();


        $validator = Validator::make($request->all(), [
            'password' => 'required|string', 
        ]); if($validator->fails()){ return response()->json([
            'status' => 'error', 
            'error' => 'required_password', 
            'field' => 'password'
        ]); }
        $password = $request->get('password');
        if (password_verify($password,  User::where('id', $id)->value('password')) == false)
        {
            return response()->json([
                'status' => 'error', 
                'error' => 'bad_password'
            ]);
        }

        $user->delete();
        return response()->json(['status' => 'success']); 
    }

    public function logout(Request $request) {
        
        $token = JWTAuth::parseToken();
        try {
            JWTAuth::invalidate($token);
            return response()->json([
                'status' => 'success', 
                'message'=> "User successfully logged out."
            ]);
        } catch (JWTException $e) {
            return response()->json([
              'status' => 'error', 
              'message' => 'Failed to logout, please try again.',
              'test' => $token
            ], 500);
        }
    }

    public function getAuthenticatedUser()
    {
        try { 
            if (! $user = JWTAuth::parseToken()->authenticate()) 
            {
                return response()->json(['user_not_found'], 404);
            }
        }
        catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) 
        {
            return response()->json(['token_expired'], $e->getStatusCode());
        } 
        catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) 
        {
            return response()->json(['token_invalid'], $e->getStatusCode());
        } 
        catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['token_absent'], $e->getStatusCode());
        }
        return response()->json(compact('user'));
    }
}