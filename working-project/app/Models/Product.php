<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SoftDeletes;
class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'price',
        'stock',
        'description',
        'photo',
        'discount_min_qty',
        'discount',
        'stock_lower_limit',
        'stock_upper_limit',
    ];

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    // A product has many stock orders
    public function supplyOrder()
    {
        return $this->hasMany(supplyOrder::class);
    }

    // A product belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function items_orders()
{
    return $this->hasMany(ItemsOrder::class);
}
}
