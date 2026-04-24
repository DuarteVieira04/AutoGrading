<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{
    protected $fillable = [
        'card_id',
        'type',
        'value',
        'date',
        'debit_type',
        'credit_type',
        'payment_type',
        'payment_reference',
        'order_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
