<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplyOrderFormRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'registered_by_user_id' => 'required|exists:users,id',
            'status' => 'required|in:requested,completed',
            'quantity' => 'required|integer|min:1',
        ];
    }
}
