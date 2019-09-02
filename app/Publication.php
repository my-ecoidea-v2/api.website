<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    protected $table = 'publications';

    public function scopePublished($query){
        return $query->where('published', true);
    }

    public function isPublished(){
        return $this->published;
    }
}
