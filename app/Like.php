<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    public $table = 'user-like';
    public $timestamps = false;
}
