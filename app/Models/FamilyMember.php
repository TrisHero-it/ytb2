<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'family_id', 'order_code', 'product_name', 'email', 'region', 'purchase_date', 'raw_text',
])]
class FamilyMember extends Model
{
    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}
