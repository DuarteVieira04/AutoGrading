<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stock_adjustments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'registered_by_user_id',
        'quantity_changed',
        'custom',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    public function registeredByUser()
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }
}
