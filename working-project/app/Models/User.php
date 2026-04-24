<?php

namespace App\Models;

// Importação do Enum para o tipo de utilizador
use App\Enums\UserType;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    // Utiliza os traits standard do Laravel e o SoftDeletes
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Os atributos que podem ser preenchidos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'blocked',
        'gender',
        'photo',
        'nif',
        'default_delivery_address',
        'default_payment_type',
        'default_payment_reference',
    ];

    /**
     * Os atributos que devem ser escondidos durante a serialização.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     * Esta é a forma moderna e correta de definir os casts, combinando as melhores
     * ideias de ambos os ficheiros e removendo a redundância.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'type' => UserType::class, // Conversão para Enum
        'blocked' => 'boolean',      // Conversão para booleano
    ];

    /*
    |--------------------------------------------------------------------------
    | Relações do Modelo (Relationships)
    |--------------------------------------------------------------------------
    */

    /**
     * Um utilizador pode ter muitas encomendas (como membro).
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'member_id');
    }

    /**
     * Um utilizador pode ter vários cartões.
     * Mantida a relação hasMany por ser mais flexível e consistente.
     */
    public function card()
    {
        return $this->hasOne(Card::class, 'id', 'id');
    }

    /**
     * Um utilizador (funcionário/admin) pode registar muitos ajustes de stock.
     */
    public function registeredStockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'registered_by_user_id');
    }

    /**
     * Um utilizador (funcionário/admin) pode registar muitas encomendas a fornecedores.
     */
    public function registeredStockOrders(): HasMany
    {
        return $this->hasMany(SupplyOrder::class, 'registered_by_user_id');
    }


    /*
    |--------------------------------------------------------------------------
    | Métodos Auxiliares (Accessors & Helpers)
    |--------------------------------------------------------------------------
    */

    /**
     * Obtém as iniciais do nome completo do utilizador.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    /**
     * Obtém a inicial do primeiro e do último nome.
     */
    public function firstLastInitial(): string
    {
        $allNames = Str::of($this->name)->explode(' ');
        $firstName = $allNames->first() ?? '';
        $lastName = $allNames->count() > 1 ? $allNames->last() : '';
        return Str::of($firstName)->substr(0, 1)
            ->append(Str::of($lastName)->substr(0, 1));
    }

    /**
     * Obtém o primeiro e o último nome do utilizador.
     */
    public function firstLastName(): string
    {
        $allNames = Str::of($this->name)->explode(' ');
        $firstName = $allNames->first() ?? '';
        $lastName = $allNames->count() > 1 ? $allNames->last() : '';
        return Str::of($firstName)
            ->append(' ')
            ->append(Str::of($lastName));
    }
}
