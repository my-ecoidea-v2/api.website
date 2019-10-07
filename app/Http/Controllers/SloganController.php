<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Slogan;

class SloganController extends Controller
{
    public static function create(Request $request, $token)
    {
        $validator = Validator::make($request->all(), [
            'texte' => 'required', 
        ]); if($validator->fails()){ return response()->json([
            'status'=>'error',
            'error' => 'required_description']); }

        $Slogan = new Slogan();
        $Slogan->token = $token;
        $Slogan->texte = $request->get('texte');

        $Slogan->save();

        return response()->json(['status'=>'success']);
    }

    public static function get($publication, $token)
    {
        $Slogan = Slogan::where('token', $token)->get()->first();
        $publication->content = $Slogan;
        unset($publication->content->token);
    }
}
