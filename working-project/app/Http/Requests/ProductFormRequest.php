<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductFormRequest extends FormRequest
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
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'discount_min_qty' => 'nullable|integer',
            'discount' => 'nullable|numeric',
            'stock_lower_limit' => 'nullable|integer|min:0',
            'stock_upper_limit' => 'nullable|integer|min:0',
        ];
            if ($this->input('discount_min_qty') === null) {
                $this->merge(['discount_min_qty' => 0]);
            }
            if ($this->input('dicount') === null) {
                $this->merge(['discount' => 0]);
            }
    }
}
