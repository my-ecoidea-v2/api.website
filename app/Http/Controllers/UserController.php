<?php

namespace App\Http\Controllers;

use App\User;
use App\Config;
use App\UserKey;
use App\Like;
use App\Favoris;
use App\Idea;
use App\Publication;
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
                return response()->json(['status'=>'error','error' => 'invalid_email_or_password']);
            }
        } catch (JWTException $e) {
            return response()->json(['status'=>'error','error' => 'could_not_create_token, contact administrator'], 500);
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

    public static function getRole($user)
    {
        $role = $user->role;
        if ($role == 1|| $role == 2 || $role == 3)
        {
            return 1;
        } else { return 0; }
    }

    public function meIdea(Request $request)
    {
        $id = JWTAuth::parseToken()->toUser()->id;
        
        $publications = Publication::where('user_id', $id)->get();

        foreach($publications as $publication)
        {
            $token = $publication->token;
            if (Idea::where('token', $token)->exists())
            {
                IdeaController::getFast($publication, $token);
            }

            $id = $publication->user_id;
            $publication->likes = Like::where('token', $token)->count();
            $publication->favoris = Favoris::where('token', $token)->count();
            
            if (Like::where('user', JWTAuth::parseToken()->toUser()->id)
            ->where('token', $token)->exists())
            { $publication->isLike = 1; } else { $publication->isLike = 0; }

            if (Favoris::where('user', JWTAuth::parseToken()->toUser()->id)
            ->where('token', $token)->exists())
            { $publication->isFavoris = 1; } else { $publication->isFavoris = 0; }
        }
        $publications = json_decode(json_encode($publications));
        return response()->json(compact('publications'));
    }

    public function meFavoris(Request $request)
    {
        $id = JWTAuth::parseToken()->toUser()->id;
        
        $favoris = Favoris::where('user', $id)->get();

        foreach($favoris as $token)
        {
            $publication = Publication::where('token', $token->token)->first();
            if (Idea::where('token', $token->token)->exists())
            {
                $idea = Idea::where('token', $token->token)->get()->first();
                
                $publication->likes = Like::where('token', $token)->count();
                $publication->favoris = Favoris::where('token', $token)->count();
                
                if (Like::where('user', JWTAuth::parseToken()->toUser()->id)
                ->where('token', $token)->exists())
                { $publication->isLike = 1; } else { $publication->isLike = 0; }
    
                if (Favoris::where('user', JWTAuth::parseToken()->toUser()->id)
                ->where('token', $token)->exists())
                { $publication->isFavoris = 1; } else { $publication->isFavoris = 0; }
                $publication->content = $idea;
                unset($publication->content->token);
            }
            unset($token->token);
            $token->idea = $publication;
        }
        return response()->json(compact('favoris'));
    }

    public function mePublications(Request $request)
    {
        $id = JWTAuth::parseToken()->toUser()->id;
        
        $publications = Publication::where('user', $id)->get();

        foreach($publications as $publication)
        {
            $token = $publication->token;
            if (Idea::where('token', $token)->exists())
            {
                IdeaController::getFast($publication, $token);
            }
            if (Slogan::where('token', $token)->exists())
            {
                SloganController::get($publication, $token);
            }

            $id = $publication->user_id;
            $publication->user = User::where('id', $id)->get('name')->first();
            $publication->likes = Like::where('token', $token)->count();
            $publication->favoris = Favoris::where('token', $token)->count();
            
            if (Like::where('user', JWTAuth::parseToken()->toUser()->id)
            ->where('token', $token)->exists())
            { $publication->isLike = 1; } else { $publication->isLike = 0; }

            if (Favoris::where('user', JWTAuth::parseToken()->toUser()->id)
            ->where('token', $token)->exists())
            { $publication->isFavoris = 1; } else { $publication->isFavoris = 0; }

            if (Seen::where('user', JWTAuth::parseToken()->toUser()->id)
            ->where('token', $token)->exists())
            { $publication->isSeen = 1; } else { $publication->isSeen = 0; }
        }
        return response()->json(compact('publications'));
    }
}