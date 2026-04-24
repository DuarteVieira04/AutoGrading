<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OperationFormRequest extends FormRequest
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
            'card_id' => ['required', 'exists:cards,id'],
            'type' => ['required', 'in:credit,debit'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date_format:Y-m-d'],
            'debit_type' => ['nullable', 'required_if:type,debit', 'in:order,membership_fee'],
            'credit_type' => ['nullable', 'required_if:type,credit', 'in:payment,order_cancellation'],
            'payment_type' => [
                'nullable',
                'required_if:type,credit',
                'required_if:credit_type,payment',
                'in:Visa,PayPal,MB WAY'
            ],
            'payment_reference' => [
                'nullable',
                'required_if:type,credit',
                'required_if:credit_type,payment',
                'string'
            ],
            'order_id' => [
                'nullable',
                'required_if:debit_type,order',
                'required_if:credit_type,order_cancellation',
                'exists:orders,id'
            ],
        ];
    }
}
