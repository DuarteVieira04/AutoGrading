<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Adicionado
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Adicionado

class Card extends Model
{
    use HasFactory, SoftDeletes; // Adicionado para usar Soft Deletes

    /**
     * Informa ao Eloquent que a chave primária não é auto-incrementing.
     * @var bool
     */
    public $incrementing = false;

    /**
     * Os atributos que podem ser preenchidos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',             // <-- CORREÇÃO 1: Adicionado o 'id'
        'card_number',
        'balance',
    ];

    public function user()
    {
        // CORREÇÃO 2: Especificamos que a chave estrangeira é a coluna 'id'
        return $this->belongsTo(User::class, 'id');
    }


    public function operations()
    {
        return $this->hasMany(Operation::class);
    }
}

