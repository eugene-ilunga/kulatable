<?php

namespace Modules\RestApi\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request for recording cash transactions (cash-in, cash-out, safe-drop).
 *
 * @api POST /api/application-integration/pos/cash-register/transactions/*
 */
class CashTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'reason' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:255',
        ];
    }

    /**
     * Custom attribute names for validation messages.
     */
    public function attributes(): array
    {
        return [
            'amount' => 'transaction amount',
            'reason' => 'transaction reason',
            'reference' => 'reference number',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'The transaction amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be greater than zero.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
