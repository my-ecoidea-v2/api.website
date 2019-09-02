<?php

namespace App\Http\Controllers;

use App\PEco_idea;
use App\Keywords;
use App\Links;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PEco_IdeaController extends Controller
{   
    public static function create(Request $request, $token)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required', 
        ]); if($validator->fails()){ return response()->json([
            'status'=>'error',
            'error' => 'required_description']); }
        $validator = Validator::make($request->all(), [
            'keyword_1' => 'required', 
        ]); if($validator->fails()){ return response()->json([
            'status'=>'error',
            'error' => 'required_keyword_1']); }
        $validator = Validator::make($request->all(), [
            'keyword_2' => 'required', 
        ]); if($validator->fails()){ return response()->json([
            'status'=>'error',
            'error' => 'required_keyword_2']); }
        $validator = Validator::make($request->all(), [
            'keyword_3' => 'required', 
        ]); if($validator->fails()){ return response()->json([
            'status'=>'error',
            'error' => 'required_keyword_3']); }
        $validator = Validator::make($request->all(), [
            'categorie_id' => 'required', 
        ]); if($validator->fails()){ return response()->json([
            'status'=>'error',
            'error' => 'required_categorie_id']); }
        $validator = Validator::make($request->all(), [
            'texte' => 'required', 
        ]); if($validator->fails()){ return response()->json([
            'status'=>'error',
            'error' => 'required_texte']); }

        $idea = new PEco_Idea();
        $idea->token = $token;
        $idea->description = $request   ->get('description');
        $idea->categorie_id = $request     ->get('categorie_id');
        $idea->texte = $request         ->get('texte');

        $keyword = new Keywords();
        $keyword->token = $token;
        $keyword->keyword = $request->get('keyword_1');
        $keyword->save();
        $keyword = new Keywords();
        $keyword->token = $token;
        $keyword->keyword = $request->get('keyword_2');
        $keyword->save();
        $keyword = new Keywords();
        $keyword->token = $token;
        $keyword->keyword = $request->get('keyword_3');
        $keyword->save();

        $validator = Validator::make($request->all(), ['link_1' => 'required|string']); 
        if(!($validator->fails())) { 
            $link = new Links();
            $link->token = $token;
            $link->link = $request->get('link_1');
            $link->save();
        }
        $validator = Validator::make($request->all(), ['link_2' => 'required|string']); 
        if(!($validator->fails())) { 
            $link = new Links();
            $link->token = $token;
            $link->link = $request->get('link_2');
            $link->save();
        }
        $validator = Validator::make($request->all(), ['link_3' => 'required|string']); 
        if(!($validator->fails())) { 
            $link = new Links();
            $link->token = $token;
            $link->link = $request->get('link_3');
            $link->save();
        }
        $idea->save();

        return response()->json(['status'=>'success']);
    }
    public static function get($publication, $token)
    {
        $idea = PEco_Idea::where('token', $token)->get()->first();
        $idea->keywords = Keywords::where('token', $token)->get();
        if (!Links::where('token', $token)->doesntExist())
        {
            $idea->links = Links::where('token', $token)->get();
        }
        $publication->content = $idea;
        unset($publication->content->token);
    }
}
