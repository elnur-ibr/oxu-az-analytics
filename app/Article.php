<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    public $fillable = [
        'base_url',
        'url',
        'title',
        'published',
        'view',
    ];
}
