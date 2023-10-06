<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SuperAdmin extends Model
{
    protected $guarded = ['id'];
    protected $appends = ['image_url'];
}
