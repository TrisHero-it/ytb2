<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id', 'family_id', 'user_id', 'user_name', 'status', 'old_value', 'new_value', 'name_product', 'email',
])]
class History extends Model
{
    const UPDATED_AT = null;

    protected $table = 'history_joining_family';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
