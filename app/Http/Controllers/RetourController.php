<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Retour;
use JWTAuth;

class RetourController extends Controller
{
    public function retour(Request $request)
    {
        $id = JWTAuth::parseToken()->toUser()->id;   
        
        $retour = new Retour();
        $retour->user = $id;
        $retour->texte = $request->get('texte');
        $retour->save();

        return response()->json(['status'=>'success']);
    }
}
