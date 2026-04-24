<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SoftDeletes;
class SupplyOrder extends Model
{
    protected $fillable = [
        'id',
        'product_id',
        'registered_by_user_id',
        'status',
        'quantity',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Each stock order is registered by a user
    public function registeredByUser()
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }
}
