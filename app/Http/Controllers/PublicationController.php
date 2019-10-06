<?php

namespace App\Http\Controllers;

use App\User;
use App\Publication;
use App\Idea;
use App\Like;
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
            'user_id' => 'required', 
        ]); if($validator->fails()){ return response()->json([
            'status'=>'error',
            'error' => 'required_user_id'
        ]); }
        $validator = Validator::make($request->all(), [
            'type_id' => 'required', 
        ]); if($validator->fails()){ return response()->json([
            'status'=>'error',
            'error' => 'required_type_id'
        ]); }
        $validator = Validator::make($request->all(), [
            'anonyme' => 'required', 
        ]); if($validator->fails()){ return response()->json([
            'status'=>'error',
            'error' => 'required_anonyme'
        ]); }

        $token = str_random(250);
        $validator = Validator::make($request->all(), [
            'token' => 'unique', 
        ]); while($validator->fails()){ 
            $token = str_random(250);
        }

        $type = $request->get('type_id');
        if ($type == 1)
        {   
            $idea = IdeaController::create($request, $token);
            $idea = json_decode(json_encode($idea));
            if ($idea->original->status=='error')
            {
                $error = $idea->original->error;
                return response()->json(['error'=>$error]);
            }
        } else return response()->json(['status'=>'error', 'error'=>'invalid_type']);

        $user = JWTAuth::parseToken()->authenticate();

        $publication = new Publication();
        $publication->user_id = $user  ->get('id');
        $publication->type_id = $request  ->get('type_id');
        $publication->anonyme = $request  ->get('anonyme');

        $publication->token = $token;

        $publication->save();
        
        return response()->json(['token' => $token]);
    }

    public function get(Request $request)
    {
        $token = $request->get('token');
        $publication = Publication::where('token', $token)->first();
        $token = $publication->token;
        if (Idea::where('token', $token)->exists())
        {
            IdeaController::get($publication, $token);
        }
        return response()->json(json_decode(json_encode($publication)));
    }

    public function publish(Request $request){

        $id = $request->get('id');
        $publication = Publication::where('id', $id)->get()->first();

        $publication->published = true;
        $publication->acceptBy = $request->get('user_id');
        $publication->save();

        return response()->json(['status' => 'success']);
    }

    public function delete(Request $request){

        $id = $request->get('id');
        $publication = Publication::where('id', $id)->get()->first();

        $publication_deleted = new Publication_deleted();
        $publication_deleted->user_id = Publication::where('id', $id)->value('user_id');
        $publication_deleted->type_id = Publication::where('id', $id)->value('type_id');
        $publication_deleted->token = Publication::where('id', $id)->value('token');
        $publication_deleted->deleteBy = $request->get('user_id');
        $publication_deleted->deleteReason = $request->get('reason');
        $publication_deleted->save();

        $publication->delete();
        return response()->json(['status' => 'success']);
    }

    public function getAll(Request $request)
    {
        $publications = Publication::all();
        $publications = json_decode($publications);
        foreach($publications as $publication)
        {
            $token = $publication->token;
            if (Idea::where('token', $token)->exists())
            {
                IdeaController::get($publication, $token);
            }

            $id = $publication->user_id;
            $publication->user = User::where('id', $id)->get()->first();
            $publication->likes = Like::where('token', $token)->count();
            if (Like::where('user', JWTAuth::parseToken()->toUser()->id)
            ->where('token', $token)->exists())
            {
                $publication->isLike = 1;
            } else {
                $publication->isLike = 0;
            }
        }
        $publications = json_decode(json_encode($publications));
        return response()->json(compact('publications'));
    }

    public function getFast(Request $request)
    {
        $publications = Publication::all();
        $publications = json_decode($publications);
        foreach($publications as $publication)
        {
            $token = $publication->token;
            if (Idea::where('token', $token)->exists())
            {
                IdeaController::getFast($publication, $token);
            }

            $id = $publication->user_id;
            $publication->user = User::where('id', $id)->get('name')->first();
            $publication->likes = Like::where('token', $token)->count();
            if (Like::where('user', JWTAuth::parseToken()->toUser()->id)
            ->where('token', $token)->exists())
            {
                $publication->isLike = 1;
            } else {
                $publication->isLike = 0;
            }
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
        }
    }
} 
