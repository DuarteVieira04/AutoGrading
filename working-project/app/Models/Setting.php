<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SoftDeletes;

class Setting extends Model
{
    protected $table = 'settings';
    protected $fillable = [
        'membership_fee',
    ];
}
