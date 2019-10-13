<?php

namespace App\Http\Controllers;

use App\User;
use App\Publication;
use App\Idea;
use App\Seen;
use App\Like;
use App\Slogan;
use App\Favoris;
use App\Publication_deleted;
use App\Http\Controllers\IdeaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Routing\ResponseFactory;
use JWTAuth;

class PublicationController extends Controller
{

    public function create(Request $request){
        
        $validator = Validator::make($request->all(), [
            'anonyme' => 'required', 
        ]); if($validator->fails()){ return response()->json([
            'status'=>'error',
            'error' => 'required_anonyme'
        ]); }

        $token = str_random(50);
        $validator = Validator::make($request->all(), [
            'token' => 'unique', 
        ]); while($validator->fails()){ 
            $token = str_random(50);
        }

        $type = $request->get('type');
        if ($type == 1)
        {   
            $idea = IdeaController::create($request, $token);
            $idea = json_decode(json_encode($idea));
            if ($idea->original->status=='error')
            {
                $error = $idea->original->error;
                return response()->json(['error'=>$error]);
            }
        }
        else if ($type == 2)
        {   
            $idea = SloganController::create($request, $token);
            $idea = json_decode(json_encode($idea));
            if ($idea->original->status=='error')
            {
                $error = $idea->original->error;
                return response()->json(['error'=>$error]);
            } 
        }
        else return response()->json(['status'=>'error', 'error'=>'invalid_type']);

        $id = JWTAuth::parseToken()->toUser()->id;   

        $publication = new Publication();
        $publication->user_id = $id;
        $publication->type_id = $request  ->get('type');
        $publication->anonyme = $request  ->get('anonyme');

        if (UserController::getRole(JWTAuth::parseToken()->toUser()) == 3)
        {
            $publication->published = true;
        }

        $publication->token = $token;

        $publication->save();
        
        return response()->json(['token' => $token]);
    }

    public function get(Request $request)
    {
        $token = $request->get('token');
        if (Publication::where('token', $token)->doesntExist())
        {
            return response()->json(['status'=>'error','error'=>'invalid']);
        }
        $publication = Publication::where('token', $token)->first();

        if ($publication->published == 0)
        {
            if (UserController::getRole(JWTAuth::parseToken()->toUser()) != 1
            || UserController::getRole(JWTAuth::parseToken()->toUser()) != 2)
            {
                return response()->json(["status"=>"error", "error"=>"permission_lost"]);
            }
        }

        $token = $publication->token;
        if (Idea::where('token', $token)->exists())
        {
            IdeaController::get($publication, $token);
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

        return response()->json(compact('publication'));
    }

    public function publish(Request $request){

        $token = $request->get('token');
        $publication = Publication::where('token', $token)->get()->first();
        $id = JWTAuth::parseToken()->toUser()->id;   

        $publication->published = true;
        $publication->acceptBy = $id;
        $publication->save();

        return response()->json(['status' => 'success']);
    }

    public function delete(Request $request){

        $id = $request->get('token');
        $publication = Publication::where('token', $token)->get()->first();
        $id = JWTAuth::parseToken()->toUser()->id;   

        $publication_deleted = new Publication_deleted();
        $publication_deleted->user_id = Publication::where('token', $token)->value('user_id');
        $publication_deleted->type_id = Publication::where('token', $token)->value('type_id');
        $publication_deleted->token = Publication::where('token', $token)->value('token');
        $publication_deleted->deleteBy = $id;
        $publication_deleted->deleteReason = $request->get('reason');
        $publication_deleted->save();

        $publication->delete();
        return response()->json(['status' => 'success']);
    }

    // public function getAll(Request $request)
    // {
    //     $publications = Publication::where('published', true)->get();

    //     foreach($publications as $publication)
    //     {
    //         $token = $publication->token;
    //         if (Idea::where('token', $token)->exists())
    //         {
    //             IdeaController::get($publication, $token);
    //         }

    //         $id = $publication->user_id;
    //         $publication->user = User::where('id', $id)->get()->first();

    //         $publication->likes = Like::where('token', $token)->count();
    //         $publication->favoris = Favoris::where('token', $token)->count();
            
    //         if (Like::where('user', JWTAuth::parseToken()->toUser()->id)
    //         ->where('token', $token)->exists())
    //         { $publication->isLike = 1; } else { $publication->isLike = 0; }

    //         if (Favoris::where('user', JWTAuth::parseToken()->toUser()->id)
    //         ->where('token', $token)->exists())
    //         { $publication->isFavoris = 1; } else { $publication->isFavoris = 0; }
    //     }
    //     $publications = json_decode(json_encode($publications));
    //     return response()->json(compact('publications'));
    // }

    public function getFast(Request $request)
    {
        $publications = Publication::where('published', true)->inRandomOrder()->get();
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

    public function getModeration(Request $request)
    {
        if (UserController::getRole(JWTAuth::parseToken()->toUser()) != 1
        || UserController::getRole(JWTAuth::parseToken()->toUser()) != 2)
        {
            return response()->json(["status"=>"error", "error"=>"permission_lost"]);
        }
        $publications = Publication::where('published', false)->get();
        foreach($publications as $publication)
        {
            $token = $publication->token;
            if (Idea::where('token', $token)->exists())
            {
                IdeaController::getFast($publication, $token);
            }

            $id = $publication->user_id;
            $publication->user = User::where('id', $id)->get('name')->first();
        }
        $publications = json_decode(json_encode($publications));
        return response()->json(compact('publications'));
    }

    public function like(Request $request)
    {
        $token = $request->get('token');
        $id = JWTAuth::parseToken()->toUser()->id;     

        if(Like::where('user', $id)
        ->where('token', $token)
        ->doesntExist())
        {
            $like = new Like();
            $like->user = $id;
            $like->token = $token;

            $like->save();
        } else {
            $like = Like::where('user', $id)
            ->where('token', $token)
            ->get()->first();

            $like->delete();
        }
    }

    public function favoris(Request $request)
    {
        $token = $request->get('token');
        $id = JWTAuth::parseToken()->toUser()->id;     

        if(Favoris::where('user', $id)
        ->where('token', $token)
        ->doesntExist())
        {
            $favoris = new Favoris();
            $favoris->user = $id;
            $favoris->token = $token;

            $favoris->save();
        } else {
            $favoris = Favoris::where('user', $id)
            ->where('token', $token)
            ->get()->first();

            $favoris->delete();
        }
    }

    public function seen(Request $request)
    {
        $token = $request->get('token');
        $id = JWTAuth::parseToken()->toUser()->id;  
        
        if (Publication::where('token', $token)->doesntExist())
        {
            return response()->json(["status" => "error", 'error' => 'token_dosentExist']);
        }

        $seen = new Seen();
        $seen->user = $id;
        $seen->token = $token;

        $seen->save();

        return response()->json(["status" => "success"]);
    }
} 
