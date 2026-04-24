<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemsOrderFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id'     => 'required|integer|exists:orders,id',
            'product_id'   => 'required|integer|exists:products,id',
            'quantity'     => 'required|integer|min:1',
            'unit_price'   => 'required|numeric|min:0',
            'discount'     => 'nullable|numeric|min:0',
            'subtotal'     => 'required|numeric|min:0',
            // Add other validation rules as needed
        ];
    }
}