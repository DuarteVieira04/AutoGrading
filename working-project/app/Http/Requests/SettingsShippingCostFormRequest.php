<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsShippingCostFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Allow all users to make this request (adjust as needed)
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
            'min_value_threshold' => ['required', 'numeric', 'min:0'],
            'max_value_threshold' => ['required', 'numeric', 'gt:min_value_threshold'],
            'shipping_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
