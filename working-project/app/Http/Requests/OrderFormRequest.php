<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member_id' => 'required|integer',
            'status' => 'required|in:pending,completed,canceled',
            'date' => 'required|date',
            'total_items' => 'required|integer',
            'shipping_cost' => 'required|numeric',
            'total' => 'required|numeric',
            'nif' => 'nullable|string',
            'delivery_address' => 'required|string',
            'pdf_receipt' => 'nullable|string',
            'cancel_reason' => 'nullable|string',
        ];
    }
}
