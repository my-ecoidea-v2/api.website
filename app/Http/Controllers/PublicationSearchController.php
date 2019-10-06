<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Keywords;
use App\Idea;
use App\Links;
use App\Publication;
use App\Http\Controllers\PublicationController;
use Illuminate\Support\Collection;

class PublicationSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('awser');
        $ideasToken = Keywords::where(function($awser) use ($query) {
            $awser->where('keyword', 'LIKE', '%'.$query.'%');
        })->get('token');
        foreach($ideasToken as $token)
        {
            $publication = Publication::where('token', $token->token)->first();
            if (Idea::where('token', $token->token)->exists())
            {
                $idea = Idea::where('token', $token->token)->get()->first();
                $idea->keywords = Keywords::where('token', $token->token)->get();
                if (!Links::where('token', $token->token)->doesntExist())
                {
                    $idea->links = Links::where('token', $token->token)->get();
                }
                $publication->content = $idea;
                unset($publication->content->token);
            }
            unset($token->token);
            $token->idea = $publication;
        }
        $ideasText = Idea::where(function($awser) use ($query) {
            $awser->where('texte', 'LIKE', '%'.$query.'%');
        })->get('token');
        foreach($ideasText as $token)
        {
            $publication = Publication::where('token', $token->token)->first();
            if (Idea::where('token', $token->token)->exists())
            {
                $idea = Idea::where('token', $token->token)->get()->first();
                $idea->keywords = Keywords::where('token', $token->token)->get();
                if (!Links::where('token', $token->token)->doesntExist())
                {
                    $idea->links = Links::where('token', $token->token)->get();
                }
                $publication->content = $idea;
                unset($publication->content->token);
            }
            unset($token->token);
            $token->idea = $publication;
        }
        if ($ideasText->isEmpty() && $ideasText->isEmpty())
        {
            return response()->json(['status'=>'empty']);
        }
        $result = $ideasToken->merge($ideasText);
        return response()->json(compact('result'));
    }
}
