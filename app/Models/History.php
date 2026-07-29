<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'order_id', 'family_id', 'status', 'old_value', 'new_value', 'name_product', 'email',
])]
class History extends Model
{
    const UPDATED_AT = null;

    protected $table = 'history_joining_family';
}
