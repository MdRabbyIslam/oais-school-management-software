<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
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
    public function rules()
    {
        return [
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'mobile_money', 'cheque'])],
            'transaction_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'allocate_to_items' => 'sometimes|boolean',
            'allocations' => 'required_if:allocate_to_items,true|array',
            'allocations.*.item_id' => 'required_if:allocate_to_items,true|exists:invoice_items,id',
            'allocations.*.amount' => 'required_if:allocate_to_items,true|numeric|min:0'
        ];
    }
}
