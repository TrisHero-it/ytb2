<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['content', 'status'])]
class Collaborator extends Model
{
    public $timestamps = false;

    protected $table = 'guild_collaborators';
}
