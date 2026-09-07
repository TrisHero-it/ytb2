<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'payment_at',
    'next_payment_at',
    'email',
    'number_phone',
    'number_bank',
    'name_bank',
    'user',
    'afiilicate_by',
    'bill_payment',
    'bill_of_master',
    'note',
])]
class Family extends Model
{
    public $timestamps = false;

    protected $table = 'families';

    protected function casts(): array
    {
        return [
            'payment_at' => 'date',
            'next_payment_at' => 'date',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class)->orderBy('id');
    }
}
