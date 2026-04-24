<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; // <-- Adicionado
use Illuminate\Validation\Rule;       // <-- Adicionado

class CardFormRequest extends FormRequest
{

    public function authorize(): bool
    {
        // Alterado para permitir a ação se o utilizador for um administrador
        return Auth::user()->type === \App\Enums\UserType::BOARD;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Obtém o ID do cartão da rota (só existe ao ATUALIZAR, é nulo ao CRIAR)
        $cardId = $this->route('card')?->id;

        return [
            // O 'id' do cartão (que também é o user_id) deve ser um utilizador que existe e não pode ter já um cartão
            'id' => [
                'required',
                'integer',
                'exists:users,id',
                // A regra 'unique' só se aplica ao CRIAR um novo cartão (método POST)
                $this->isMethod('post') ? Rule::unique('cards', 'id') : ''
            ],
            'card_number' => [
                'required',
                'numeric',
                'digits:6', // Mais específico que min/max
                // Garante que o número do cartão é único na base de dados,
                // ignorando o próprio cartão quando o estamos a ATUALIZAR.
                Rule::unique('cards', 'card_number')->ignore($cardId),
            ],
            'balance' => 'required|numeric|min:0',
        ];
    }
}
